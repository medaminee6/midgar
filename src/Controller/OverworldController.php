<?php

namespace App\Controller;

use App\Repository\GameRunRepository;
use App\Repository\PersonnageRepository;
use App\Repository\UniverseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/overworld')]
class OverworldController extends AbstractController
{
    #[Route('/{personnageId}', name: 'overworld_play', methods: ['GET'])]
    public function play(
        string $personnageId,
        PersonnageRepository $personnageRepo,
        GameRunRepository $gameRunRepo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $personnageId = (int) $personnageId;
        $personnage = $personnageRepo->find($personnageId);
        
        if (!$personnage) {
            return $this->redirectToRoute('adventure_index');
        }
        
        // Check if we should start a new run
        $newRun = (bool) $request->query->get('newRun', false);
        $requestedRunId = $request->query->getInt('runId', 0);
        
        // Load or create game run
        $gameRun = null;
        if ($requestedRunId > 0) {
            $candidateRun = $gameRunRepo->find($requestedRunId);
            if ($candidateRun && $candidateRun->getPersonnage()?->getId() === $personnage->getId()) {
                $gameRun = $candidateRun;
            }
        }
        if (!$gameRun && !$newRun) {
            $gameRun = $gameRunRepo->findOneBy(['personnage' => $personnage], ['updatedAt' => 'DESC', 'id' => 'DESC']);
        }
        $isFreshRun = false;
        
        // Create new run if requested or if no run exists
        if ($newRun || !$gameRun) {
            $isFreshRun = true;
            $gameRun = new \App\Entity\GameRun();
            $gameRun->setPersonnage($personnage);
            $gameRun->setHealth(100); // Default health
            $gameRun->setMp(50); // Default MP
            $gameRun->setCoins(0);
            $gameRun->setKills(0);
            $gameRun->setLevel(1);
            $gameRun->setDefeatedEnemies([]);
            $em->persist($gameRun);
            $em->flush();
        }
        
        return $this->render('overworld/game.html.twig', [
            'personnage' => $personnage,
            'portraitImage' => $personnage->getPortraitImage(),
            'gameRun' => $gameRun,
            'savedLevel' => $gameRun->getLevel(),
            'savedKills' => $gameRun->getKills(),
            'savedCoins' => $gameRun->getCoins(),
            'savedHealth' => $gameRun->getHealth(),
            'savedMp' => $gameRun->getMp(),
            'defeatedEnemies' => $gameRun->getDefeatedEnemies(),
            'collectedCoins' => $gameRun->getCollectedCoins(),
            'isFreshRun' => $isFreshRun,
            'gameRunId' => $gameRun->getId(),
        ]);
    }

    #[Route('/saves/{personnageId}', name: 'overworld_list_saves', methods: ['GET'])]
    public function listSaves(
        string $personnageId,
        PersonnageRepository $personnageRepo,
        GameRunRepository $gameRunRepo
    ): Response {
        $personnageId = (int) $personnageId;
        $personnage = $personnageRepo->find($personnageId);

        if (!$personnage) {
            return $this->json(['success' => false, 'message' => 'Personnage not found'], 404);
        }

        $runs = $gameRunRepo->findBy(['personnage' => $personnage], ['updatedAt' => 'DESC', 'id' => 'DESC']);

        $saveList = array_map(function ($run) {
            return [
                'id' => $run->getId(),
                'level' => $run->getLevel(),
                'kills' => $run->getKills(),
                'coins' => $run->getCoins(),
                'health' => $run->getHealth(),
                'mp' => $run->getMp(),
                'updatedAt' => $run->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $runs);

        return $this->json([
            'success' => true,
            'saves' => $saveList,
        ]);
    }
    
    #[Route('/new-run/{personnageId}', name: 'overworld_new_run', methods: ['GET'])]
    public function newRun(
        string $personnageId,
        PersonnageRepository $personnageRepo,
        EntityManagerInterface $em
    ): Response {
        $personnageId = (int) $personnageId;
        $personnage = $personnageRepo->find($personnageId);
        
        if (!$personnage) {
            return $this->redirectToRoute('adventure_index');
        }
        
        // Create fresh run
        $gameRun = new \App\Entity\GameRun();
        $gameRun->setPersonnage($personnage);
        $gameRun->setHealth(100);
        $gameRun->setMp(50);
        $gameRun->setCoins(0);
        $gameRun->setKills(0);
        $gameRun->setLevel(1);
        $gameRun->setDefeatedEnemies([]);
        $gameRun->setCollectedCoins([]);
        $em->persist($gameRun);
        $em->flush();
        
        return $this->redirectToRoute('overworld_play', ['personnageId' => $personnageId, 'runId' => $gameRun->getId()]);
    }
    
    #[Route('/save-progress', name: 'overworld_save_progress', methods: ['POST'])]
    public function saveProgress(
        Request $request,
        PersonnageRepository $personnageRepo,
        GameRunRepository $gameRunRepo,
        EntityManagerInterface $em
    ): Response {
        $data = json_decode($request->getContent(), true);
        
        error_log('SAVE-PROGRESS RECEIVED: ' . json_encode($data));
        
        if (!isset($data['personnageId'])) {
            return $this->json(['success' => false, 'message' => 'Invalid data'], 400);
        }
        
        $personnage = $personnageRepo->find($data['personnageId']);
        
        if (!$personnage) {
            return $this->json(['success' => false, 'message' => 'Personnage not found'], 404);
        }
        
        // Get or create game run
        $runId = isset($data['gameRunId']) ? (int) $data['gameRunId'] : 0;
        $gameRun = null;
        if ($runId > 0) {
            $candidateRun = $gameRunRepo->find($runId);
            if ($candidateRun && $candidateRun->getPersonnage()?->getId() === $personnage->getId()) {
                $gameRun = $candidateRun;
            }
        }
        if (!$gameRun) {
            $gameRun = $gameRunRepo->findOneBy(['personnage' => $personnage], ['updatedAt' => 'DESC', 'id' => 'DESC']);
        }
        if (!$gameRun) {
            $gameRun = new \App\Entity\GameRun();
            $gameRun->setPersonnage($personnage);
            $gameRun->setHealth(100);
            $gameRun->setMp(50);
            $gameRun->setCoins(0);
            $gameRun->setKills(0);
            $gameRun->setLevel(1);
            $gameRun->setDefeatedEnemies([]);
            $gameRun->setCollectedCoins([]);
        }
        
        // Update XP (still in personnage for now)
        if (isset($data['xp'])) {
            $currentXp = $personnage->getStrength() ?: 0;
            $personnage->setStrength($currentXp + $data['xp']);
        }
        
        // Update health
        if (isset($data['health'])) {
            $gameRun->setHealth($data['health']);
        }
        
        // Update MP
        if (isset($data['mp'])) {
            $gameRun->setMp($data['mp']);
        }
        
        // Update coins (from level collection)
        if (isset($data['coinsCollected'])) {
            // Set directly instead of adding (per-level coins)
            $gameRun->setCoins($data['coinsCollected']);
            error_log('Setting coins to: ' . $data['coinsCollected']);
        }
        
        // Update kills
        if (isset($data['kills'])) {
            // Set directly instead of adding (per-level kills)
            $gameRun->setKills($data['kills']);
            error_log('Setting kills to: ' . $data['kills']);
        }
        
        // Update level
        if (isset($data['currentLevel'])) {
            $gameRun->setLevel($data['currentLevel']);
        }
        
        // Update defeated enemies
        if (isset($data['defeatedEnemyId'])) {
            $gameRun->addDefeatedEnemy($data['defeatedEnemyId']);
        }
        
        // Update collected coins
        if (isset($data['collectedCoins'])) {
            $gameRun->setCollectedCoins($data['collectedCoins']);
        }

        $gameRun->setUpdatedAt(new \DateTimeImmutable());
        
        $em->persist($gameRun);
        $em->flush();
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/battle-result', name: 'overworld_battle_result', methods: ['POST'])]
    public function battleResult(
        Request $request,
        PersonnageRepository $personnageRepo,
        EntityManagerInterface $em
    ): Response {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['personnageId']) || !isset($data['enemyDefeated'])) {
            return $this->json(['success' => false, 'message' => 'Invalid data'], 400);
        }
        
        $personnage = $personnageRepo->find($data['personnageId']);
        
        if (!$personnage) {
            return $this->json(['success' => false, 'message' => 'Personnage not found'], 404);
        }
        
        // Update XP from battle
        if (isset($data['xp'])) {
            $currentXp = $personnage->getStrength() ?: 0;
            $personnage->setStrength($currentXp + $data['xp']);
        }
        
        // Update coins from battle
        if (isset($data['coins'])) {
            $currentCoins = $personnage->getMagic() ?: 0;
            $personnage->setMagic($currentCoins + $data['coins']);
        }
        
        $em->flush();
        
        return $this->json(['success' => true]);
    }
}
