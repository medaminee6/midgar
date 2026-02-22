<?php

namespace App\Controller;
use App\Repository\UniverseRepository;
use App\Repository\OeuvreRepository;
use App\Repository\ProduitRepository;
use App\Service\DiscoverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\DefiRepository;
use Symfony\Component\HttpFoundation\Request;


class PageController extends AbstractController
{



#[Route('/', name: 'home', methods: ['GET'])]
public function index(
    Request $request,
    UniverseRepository $universRepo,
    OeuvreRepository $oeuvreRepo,
    DefiRepository $defiRepo
): Response
{
    // ==============================
    // LOGIQUE MODALE FACE (AJOUT)
    // ==============================
    $user = $this->getUser();
    $session = $request->getSession();

    $showFaceModal =
        $user &&
        method_exists($user, 'isFaceEnabled') &&
        !$user->isFaceEnabled() &&
        !$session->get('face_modal_skipped', false);

    // ------------------------------
    // Univers aléatoires
    // ------------------------------
    $allUniversIds = $universRepo->createQueryBuilder('u')
        ->select('u.id')
        ->getQuery()
        ->getArrayResult();

    shuffle($allUniversIds);
    $randomIdsUnivers = array_slice(array_column($allUniversIds, 'id'), 0, 10);
    $universPopulaires = $universRepo->findBy(['id' => $randomIdsUnivers]);

    // ------------------------------
    // Œuvres récentes
    // ------------------------------
    $allOeuvresIds = $oeuvreRepo->createQueryBuilder('o')
        ->select('o.id')
        ->getQuery()
        ->getArrayResult();

    shuffle($allOeuvresIds);
    $randomIdsOeuvres = array_slice(array_column($allOeuvresIds, 'id'), 0, 10);
    $creationsRecentes = $oeuvreRepo->findBy(['id' => $randomIdsOeuvres]);

    // ------------------------------
    // Défis récents
    // ------------------------------
    $defis = $defiRepo->findBy([], ['dateDebut' => 'DESC'], 8);

    return $this->render('index.html.twig', [
        'universPopulaires' => $universPopulaires,
        'creationsRecentes' => $creationsRecentes,
        'defis' => $defis,
        'showFaceModal' => $showFaceModal, // 👈 IMPORTANT
    ]);
}




    #[Route('/discover', name: 'discover', methods: ['GET'])]
    public function discover(Request $request, DiscoverService $discoverService, EntityManagerInterface $entityManager): Response
    {
        $rankedPosts = [];
        $debugMode = (string) $request->query->get('discover_debug', '');

        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            if ($debugMode === 'user-id') {
                // DEBUG ONLY: remove after debugging
                dd($user->getId());
            }

            $rankedPosts = $discoverService->getRankedPostsForUser($user, 100, $debugMode === 'service');

            if ($rankedPosts !== []) {
                $connection = $entityManager->getConnection();

                foreach ($rankedPosts as &$post) {
                    $type = (string) ($post['type'] ?? '');
                    $id = (int) ($post['id'] ?? 0);

                    $post['imagePath'] = null;
                    $post['title'] = sprintf('%s #%d', ucfirst($type), $id);

                    if ($id <= 0) {
                        continue;
                    }

                    if ($type === 'oeuvre') {
                        $row = $connection->fetchAssociative('SELECT title, image_url FROM oeuvres WHERE id = :id', ['id' => $id]);
                        if ($row) {
                            $post['title'] = (string) ($row['title'] ?? $post['title']);
                            $post['imagePath'] = $row['image_url'] ?? null;
                        }
                    } elseif ($type === 'artefact') {
                        $row = $connection->fetchAssociative('SELECT name, image_url FROM artefacts WHERE id = :id', ['id' => $id]);
                        if ($row) {
                            $post['title'] = (string) ($row['name'] ?? $post['title']);
                            $post['imagePath'] = $row['image_url'] ?? null;
                        }
                    } elseif ($type === 'personnage') {
                        $row = $connection->fetchAssociative('SELECT name FROM personnage WHERE id = :id', ['id' => $id]);
                        if ($row) {
                            $post['title'] = (string) ($row['name'] ?? $post['title']);
                        }
                    } elseif ($type === 'universe') {
                        $row = $connection->fetchAssociative('SELECT name FROM universe WHERE id = :id', ['id' => $id]);
                        if ($row) {
                            $post['title'] = (string) ($row['name'] ?? $post['title']);
                        }
                    }
                }
                unset($post);
            }

            if ($debugMode === 'controller') {
                // DEBUG ONLY: remove after debugging
                dd($rankedPosts);
            }
        }

        return $this->render('discover.html.twig', [
            'rankedPosts' => $rankedPosts,
        ]);
    }

    #[Route('/universe-detail', name: 'legacy_universe_detail', methods: ['GET'])]
    public function legacyUniverseDetail(Request $request): Response
    {
        $id = (int) $request->query->get('id', 0);

        if ($id > 0) {
            return $this->redirectToRoute('universe_show', ['id' => $id]);
        }

        return $this->redirectToRoute('discover');
    }

    #[Route('/admin/produits', name: 'admin_produits', methods: ['GET'])]
    public function adminProduits(ProduitRepository $produitRepository): Response
    {
        return $this->render('admin/produits.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[Route(
        '/pages/{page}',
        name: 'page',
        requirements: ['page' => '[a-z0-9\-]+'],
        priority: -100,
        methods: ['GET']
    )]
    public function show(string $page): Response
    {
        $template = sprintf('pages/%s.html.twig', $page);

        if (!$this->container->get('twig')->getLoader()->exists($template)) {
            throw $this->createNotFoundException('Page not found');
        }

        return $this->render($template);
    }
}