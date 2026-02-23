<?php
namespace App\Controller;

use App\Entity\Personnage;
use App\Entity\Universe;
use App\Entity\Enemy;
use App\Entity\User;
use App\Repository\PersonnageRepository;
use App\Repository\UniverseRepository;
use App\Repository\EnemyRepository;
use App\Repository\UserRepository;
use App\Service\UniverseStoryGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/game')]
class GameApiController extends AbstractController
{
    /**
     * Get personnage data for game
     */
    #[Route('/personnage/{id}', name: 'api_game_personnage', methods: ['GET'])]
    public function getPersonnage(int $id, PersonnageRepository $repo): JsonResponse
    {
        $personnage = $repo->find($id);
        
        if (!$personnage) {
            return $this->json(['error' => 'Personnage not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $personnage->getId(),
            'name' => $personnage->getName(),
            'classRole' => $personnage->getClassRole(),
            'universe_id' => $personnage->getUniverse()?->getId(),
            'stats' => [
                'attack' => $personnage->getStrength() ?? 10,
                'defense' => $personnage->getDefense() ?? 5,
                'magic' => $personnage->getMagic() ?? 5,
                'agility' => $personnage->getAgility() ?? 8,
                'maxHp' => 100 + ($personnage->getStrength() ?? 10),
            ],
            'portrait' => $personnage->getPortraitImage(),
        ]);
    }

    /**
     * Get universe data for game
     */
    #[Route('/universe/{id}', name: 'api_game_universe', methods: ['GET'])]
    public function getUniverse(int $id, UniverseRepository $repo): JsonResponse
    {
        $universe = $repo->find($id);
        
        if (!$universe) {
            return $this->json(['error' => 'Universe not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $universe->getId(),
            'name' => $universe->getName(),
            'genre' => $universe->getGenre(),
            'shortDescription' => $universe->getShortDescription(),
            'storyContext' => $universe->getStoryContext(),
            'themes' => $universe->getThemes(),
            'bannerImage' => $universe->getBannerImage() ? base64_encode($universe->getBannerImage()) : null,
        ]);
    }

    #[Route('/universe/{id}/generated-story', name: 'api_game_generated_story', methods: ['GET'])]
    public function getGeneratedStory(
        int $id,
        Request $request,
        UniverseRepository $repo,
        PersonnageRepository $personnageRepo,
        UniverseStoryGenerator $storyGenerator
    ): JsonResponse {
        $universe = $repo->find($id);

        if (!$universe) {
            return $this->json(['error' => 'Universe not found'], Response::HTTP_NOT_FOUND);
        }

        $seedParam = $request->query->get('seed');
        $seed = is_numeric($seedParam) ? (int) $seedParam : null;
        $mode = (string) $request->query->get('mode', 'intro');
        $previousText = (string) $request->query->get('previous', '');

        $characterContext = [];
        $personnageIdParam = $request->query->get('personnageId');
        if (is_numeric($personnageIdParam)) {
            $personnage = $personnageRepo->find((int) $personnageIdParam);
            if ($personnage) {
                $characterContext = [
                    'name' => $personnage->getName() ?? 'Hero',
                    'classRole' => $personnage->getClassRole() ?? 'Adventurer',
                ];
            }
        }

        $generated = $storyGenerator->generate($universe, $seed, $characterContext, $mode, $previousText);

        return $this->json([
            'universeId' => $universe->getId(),
            'universeName' => $universe->getName(),
            'characterName' => $characterContext['name'] ?? null,
            'characterClass' => $characterContext['classRole'] ?? null,
            'mode' => $mode,
            'story' => $generated['story'],
            'source' => $generated['source'],
            'seed' => $generated['seed'],
        ]);
    }

    /**
     * Get enemies for a universe
     */
    #[Route('/universe/{id}/enemies', name: 'api_game_universe_enemies', methods: ['GET'])]
    public function getUniverseEnemies(int $id, EnemyRepository $repo): JsonResponse
    {
        $enemies = $repo->findByUniverse($id);
        
        if (empty($enemies)) {
            return $this->json(['error' => 'No enemies found for this universe'], Response::HTTP_NOT_FOUND);
        }

        $data = array_map(fn(Enemy $enemy) => [
            'id' => $enemy->getId(),
            'name' => $enemy->getName(),
            'type' => $enemy->getEnemyType(),
            'description' => $enemy->getDescription(),
            'stats' => [
                'attack' => $enemy->getStrength(),
                'defense' => $enemy->getDefense(),
                'magic' => $enemy->getMagic(),
                'agility' => $enemy->getAgility(),
                'maxHp' => $enemy->getMaxHp(),
            ],
            'difficulty' => $enemy->getDifficultyTier(),
            'color' => $enemy->getColorHex(),
            'portrait' => $enemy->getPortraitImage() ? base64_encode($enemy->getPortraitImage()) : null,
            'behavior' => $enemy->getBehaviorType(),
            'loot' => [
                'xp' => $enemy->getLootXp(),
                'gold' => $enemy->getLootGold(),
            ],
        ], $enemies);

        return $this->json($data);
    }

    /**
     * Get a random enemy for battle from a universe
     */
    #[Route('/universe/{id}/enemy-for-battle', name: 'api_game_random_enemy', methods: ['GET'])]
    public function getRandomEnemy(int $id, EnemyRepository $repo): JsonResponse
    {
        $enemies = $repo->findRandomForBattle($id, 1, 1);
        
        if (empty($enemies)) {
            return $this->json(['error' => 'No enemies found for this universe'], Response::HTTP_NOT_FOUND);
        }

        $enemy = $enemies[0];
        return $this->json([
            'id' => $enemy->getId(),
            'name' => $enemy->getName(),
            'type' => $enemy->getEnemyType(),
            'description' => $enemy->getDescription(),
            'stats' => [
                'attack' => $enemy->getStrength(),
                'defense' => $enemy->getDefense(),
                'magic' => $enemy->getMagic(),
                'agility' => $enemy->getAgility(),
                'maxHp' => $enemy->getMaxHp(),
            ],
            'difficulty' => $enemy->getDifficultyTier(),
            'color' => $enemy->getColorHex(),
            'portrait' => $enemy->getPortraitImage() ? base64_encode($enemy->getPortraitImage()) : null,
            'behavior' => $enemy->getBehaviorType(),
            'loot' => [
                'xp' => $enemy->getLootXp(),
                'gold' => $enemy->getLootGold(),
            ],
        ]);
    }

    /**
     * Save battle result to user profile
     */
    #[Route('/profile/{id}/battle-result', name: 'api_game_save_battle_result', methods: ['POST'])]
    public function saveBattleResult(
        int $id,
        Request $request,
        UserRepository $userRepo
    ): JsonResponse {
        $user = $userRepo->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Validate required fields
        if (empty($data['victory']) || empty($data['enemyName'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        // Here you could store battle history, update user stats, unlock achievements, etc.
        // For now, just return success
        
        return $this->json([
            'success' => true,
            'message' => 'Battle result saved',
            'xpEarned' => $data['xpGained'] ?? 0,
            'goldEarned' => $data['goldGained'] ?? 0,
        ]);
    }

    #[Route('/personnage/{id}/stat-upgrade', name: 'api_game_stat_upgrade', methods: ['POST'])]
    public function upgradeStat(
        int $id,
        Request $request,
        PersonnageRepository $repo,
        \Doctrine\ORM\EntityManagerInterface $em
    ): JsonResponse {
        $personnage = $repo->find($id);

        if (!$personnage) {
            return $this->json(['error' => 'Personnage not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $stat = $data['stat'] ?? null;
        $amount = isset($data['amount']) ? (int) $data['amount'] : 0;

        if (!$stat || $amount === 0) {
            return $this->json(['error' => 'Invalid stat upgrade'], Response::HTTP_BAD_REQUEST);
        }

        $clamp = function (?int $value) {
            $value = $value ?? 0;
            return max(0, min(100, $value));
        };

        switch ($stat) {
            case 'attack':
                $personnage->setStrength($clamp(($personnage->getStrength() ?? 0) + $amount));
                break;
            case 'defense':
                $personnage->setDefense($clamp(($personnage->getDefense() ?? 0) + $amount));
                break;
            case 'magic':
                $personnage->setMagic($clamp(($personnage->getMagic() ?? 0) + $amount));
                break;
            case 'agility':
                $personnage->setAgility($clamp(($personnage->getAgility() ?? 0) + $amount));
                break;
            default:
                return $this->json(['error' => 'Unknown stat'], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'stats' => [
                'attack' => $personnage->getStrength() ?? 0,
                'defense' => $personnage->getDefense() ?? 0,
                'magic' => $personnage->getMagic() ?? 0,
                'agility' => $personnage->getAgility() ?? 0,
            ]
        ]);
    }
}
