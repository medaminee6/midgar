<?php

namespace App\Controller;

use App\Repository\PersonnageRepository;
use App\Repository\UniverseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/adventure')]
class AdventureController extends AbstractController
{
    #[Route('/', name: 'adventure_index', methods: ['GET'])]
    public function index(
        PersonnageRepository $personnageRepo,
        UniverseRepository $universeRepo
    ): Response {
        // Get all universes
        $universes = $universeRepo->findAll();
        
        // Get all personnages
        $personnages = $personnageRepo->findAll();

        return $this->render('adventure/index.html.twig', [
            'universes' => $universes,
            'personnages' => $personnages,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/debug/database', name: 'adventure_debug_database', methods: ['GET'])]
    public function debugDatabase(
        PersonnageRepository $personnageRepo,
        UniverseRepository $universeRepo
    ): Response {
        $personnages = $personnageRepo->findAll();
        $universes = $universeRepo->findAll();

        return $this->json([
            'database' => [
                'driver' => 'sqlite',
                'file' => 'var/app.db',
            ],
            'tables' => [
                'universes' => count($universes),
                'personnages' => count($personnages),
            ],
            'data' => [
                'universes' => array_map(function($u) {
                    return [
                        'id' => $u->getId(),
                        'name' => $u->getName(),
                        'genre' => $u->getGenre(),
                    ];
                }, $universes),
                'personnages' => array_map(function($p) {
                    return [
                        'id' => $p->getId(),
                        'name' => $p->getName(),
                        'class' => $p->getClassRole(),
                        'universe_id' => $p->getUniverse()?->getId(),
                    ];
                }, $personnages),
            ],
        ]);
    }
}
