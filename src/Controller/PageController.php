<?php

namespace App\Controller;
use App\Repository\UniverseRepository;
use App\Repository\OeuvreRepository;
use App\Repository\ProduitRepository;
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
    public function discover(): Response
    {
        return $this->render('discover.html.twig');
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