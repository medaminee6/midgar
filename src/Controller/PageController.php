<?php

namespace App\Controller;

use App\Repository\UniverseRepository;
use App\Repository\OeuvreRepository;
use App\Repository\ProduitRepository;
use App\Repository\ArtefactRepository;
use App\Repository\PersonnageRepository;
use App\Repository\UserPreferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\DefiRepository;

class PageController extends AbstractController
{


    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(
        UniverseRepository $universRepo,
        OeuvreRepository $oeuvreRepo,
        DefiRepository $defiRepo
    ): Response
    {
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
        ]);
    }



    #[Route('/discover', name: 'discover', methods: ['GET'])]
    public function discover(
        UserPreferenceRepository $userPrefRepo,
        OeuvreRepository $oeuvreRepo,
        ArtefactRepository $artefactRepo,
        PersonnageRepository $personnageRepo,
        UniverseRepository $universeRepo
    ): Response
    {
        $user = $this->getUser();
        $oeuvresData = [];
        $artefactsData = [];
        $personnagesData = [];
        $universesData = [];

        // If user is logged in, get their preferences
        if ($user) {
            $userPref = $userPrefRepo->findByUserId($user->getId());
            
            if ($userPref && $userPref->getTags() && !empty(trim($userPref->getTags()))) {
                // Parse user tags - split by comma using explode
                $userTagsRaw = $userPref->getTags();
                $userTags = array_filter(array_map('trim', explode(',', $userTagsRaw)));
                
                if (!empty($userTags)) {
                    // Build dynamic OR conditions for LIKE queries with %tag%
                    // Query Oeuvres with LIKE '%#aa%'
                    $oeuvresQuery = $oeuvreRepo->createQueryBuilder('o')
                        ->where('o.tags LIKE :tag0')
                        ->setParameter('tag0', '%' . $userTags[0] . '%');
                    
                    // Add OR conditions for additional tags
                    for ($i = 1; $i < count($userTags); $i++) {
                        $oeuvresQuery->orWhere('o.tags LIKE :tag' . $i)
                            ->setParameter('tag' . $i, '%' . $userTags[$i] . '%');
                    }
                    
                    $oeuvres = $oeuvresQuery->getQuery()->getResult();
                    
                    // Query Universes with LIKE '%#aa%'
                    $universesQuery = $universeRepo->createQueryBuilder('u')
                        ->where('u.tags LIKE :tag0')
                        ->setParameter('tag0', '%' . $userTags[0] . '%');
                    
                    for ($i = 1; $i < count($userTags); $i++) {
                        $universesQuery->orWhere('u.tags LIKE :tag' . $i)
                            ->setParameter('tag' . $i, '%' . $userTags[$i] . '%');
                    }
                    
                    $universes = $universesQuery->getQuery()->getResult();
                    
                    // Query Personnages with LIKE '%#aa%'
                    $personnagesQuery = $personnageRepo->createQueryBuilder('p')
                        ->where('p.tags LIKE :tag0')
                        ->setParameter('tag0', '%' . $userTags[0] . '%');
                    
                    for ($i = 1; $i < count($userTags); $i++) {
                        $personnagesQuery->orWhere('p.tags LIKE :tag' . $i)
                            ->setParameter('tag' . $i, '%' . $userTags[$i] . '%');
                    }
                    
                    $personnages = $personnagesQuery->getQuery()->getResult();
                    
                    // Query Artefacts with LIKE '%#aa%'
                    $artefactsQuery = $artefactRepo->createQueryBuilder('a')
                        ->where('a.tags LIKE :tag0')
                        ->setParameter('tag0', '%' . $userTags[0] . '%');
                    
                    for ($i = 1; $i < count($userTags); $i++) {
                        $artefactsQuery->orWhere('a.tags LIKE :tag' . $i)
                            ->setParameter('tag' . $i, '%' . $userTags[$i] . '%');
                    }
                    
                    $artefacts = $artefactsQuery->getQuery()->getResult();
                    
                    // Convert entities to arrays for JSON serialization
                    foreach ($oeuvres as $oeuvre) {
                        $oeuvresData[] = [
                            'id' => $oeuvre->getId(),
                            'title' => $oeuvre->getTitle(),
                            'description' => $oeuvre->getDescription(),
                            'genre' => $oeuvre->getGenre(),
                            'tags' => $oeuvre->getTags(),
                            'name' => $oeuvre->getTitle(), // For JS compatibility
                        ];
                    }
                    
                    foreach ($universes as $universe) {
                        $universesData[] = [
                            'id' => $universe->getId(),
                            'name' => $universe->getName(),
                            'title' => $universe->getName(), // For JS compatibility
                            'shortDescription' => $universe->getShortDescription(),
                            'genre' => $universe->getGenre(),
                            'tags' => $universe->getTags(),
                        ];
                    }
                    
                    foreach ($personnages as $personnage) {
                        $personnagesData[] = [
                            'id' => $personnage->getId(),
                            'name' => $personnage->getName(),
                            'title' => $personnage->getName(), // For JS compatibility
                            'historyContext' => $personnage->getHistoryContext(),
                            'classRole' => $personnage->getClassRole(),
                            'tags' => $personnage->getTags(),
                        ];
                    }
                    
                    foreach ($artefacts as $artefact) {
                        $artefactsData[] = [
                            'id' => $artefact->getId(),
                            'name' => $artefact->getName(),
                            'title' => $artefact->getName(), // For JS compatibility
                            'description' => $artefact->getOrigins(),
                            'origin' => $artefact->getUniverse(),
                            'tags' => $artefact->getTags(),
                        ];
                    }
                }
            }
        }

        return $this->render('discover.html.twig', [
            'oeuvres' => $oeuvresData,
            'artefacts' => $artefactsData,
            'personnages' => $personnagesData,
            'universes' => $universesData,
        ]);
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
