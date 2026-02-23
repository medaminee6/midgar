<?php

namespace App\Controller;

use App\Repository\PersonnageRepository;
use App\Repository\UniverseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/adventure')]
class AdventureGameController extends AbstractController
{
    #[Route('/play/{personnageId}/{universeId}', name: 'adventure_play')]
    public function play(
        int $personnageId,
        int $universeId,
        PersonnageRepository $personnageRepo,
        UniverseRepository $universeRepo
    ): Response {
        $personnage = $personnageRepo->find($personnageId);
        $universe = $universeRepo->find($universeId);
        
        if (!$personnage || !$universe) {
            return $this->redirectToRoute('adventure_index');
        }
        
        return $this->render('adventure/game.html.twig', [
            'personnage' => $personnage,
            'universe' => $universe,
            'portraitImage' => $personnage->getPortraitImage(),
        ]);
    }
    
    #[Route('/save-xp', name: 'adventure_save_xp', methods: ['POST'])]
    public function saveXp(
        Request $request,
        PersonnageRepository $personnageRepo,
        EntityManagerInterface $em
    ): Response {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['personnageId']) || !isset($data['xp'])) {
            return $this->json(['success' => false, 'message' => 'Invalid data'], 400);
        }
        
        $personnage = $personnageRepo->find($data['personnageId']);
        
        if (!$personnage) {
            return $this->json(['success' => false, 'message' => 'Personnage not found'], 404);
        }
        
        // Update XP and coins
        $currentXp = $personnage->getStrength() ?: 0;
        $personnage->setStrength($currentXp + $data['xp']);
        
        $em->flush();
        
        return $this->json(['success' => true]);
    }
}
