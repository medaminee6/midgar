<?php

namespace App\DataFixtures;

use App\Entity\Enemy;
use App\Entity\Universe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GameFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Get existing universes
        $universes = $manager->getRepository(Universe::class)->findAll();

        if (empty($universes)) {
            return; // Exit if no universes exist
        }

        // Create enemies for each universe
        foreach ($universes as $universe) {
            $enemies = $this->getEnemiesForUniverse($universe);
            foreach ($enemies as $enemyData) {
                $enemy = new Enemy();
                $enemy->setName($enemyData['name']);
                $enemy->setEnemyType($enemyData['type']);
                $enemy->setDescription($enemyData['description']);
                $enemy->setStrength($enemyData['strength']);
                $enemy->setAgility($enemyData['agility']);
                $enemy->setMagic($enemyData['magic']);
                $enemy->setDefense($enemyData['defense']);
                $enemy->setMaxHp($enemyData['maxHp']);
                $enemy->setDifficultyTier($enemyData['difficulty']);
                $enemy->setColorHex($enemyData['color']);
                $enemy->setBehaviorType($enemyData['behavior']);
                $enemy->setLootXp($enemyData['xp']);
                $enemy->setLootGold($enemyData['gold']);
                $enemy->setUniverse($universe);

                $manager->persist($enemy);
            }
        }

        $manager->flush();
    }

    private function getEnemiesForUniverse(Universe $universe): array
    {
        $universeName = strtolower($universe->getName());

        // Define enemies per universal - can be expanded
        $enemyTemplates = [
            // Generic weak enemies
            [
                'name' => 'Goblin Minion',
                'type' => 'goblin',
                'description' => 'A small green creature with mischievous intent',
                'strength' => 5,
                'agility' => 8,
                'magic' => 0,
                'defense' => 2,
                'maxHp' => 15,
                'difficulty' => 1,
                'color' => '#90EE90',
                'behavior' => 'patrol',
                'xp' => 10,
                'gold' => 5,
            ],
            [
                'name' => 'Skeletal Archer',
                'type' => 'skeleton',
                'description' => 'A risen warrior commanded to fight',
                'strength' => 6,
                'agility' => 10,
                'magic' => 2,
                'defense' => 3,
                'maxHp' => 20,
                'difficulty' => 1,
                'color' => '#FFFACD',
                'behavior' => 'ranged',
                'xp' => 12,
                'gold' => 8,
            ],
            [
                'name' => 'Forest Wraith',
                'type' => 'wraith',
                'description' => 'A ghostly spirit haunting ancient woods',
                'strength' => 7,
                'agility' => 12,
                'magic' => 8,
                'defense' => 4,
                'maxHp' => 25,
                'difficulty' => 2,
                'color' => '#FFB6C1',
                'behavior' => 'aggressive',
                'xp' => 20,
                'gold' => 12,
            ],
            [
                'name' => 'Stone Golem',
                'type' => 'golem',
                'description' => 'A powerful creature of earth and stone',
                'strength' => 12,
                'agility' => 4,
                'magic' => 3,
                'defense' => 10,
                'maxHp' => 50,
                'difficulty' => 2,
                'color' => '#808080',
                'behavior' => 'aggressive',
                'xp' => 25,
                'gold' => 20,
            ],
            [
                'name' => 'Dark Sorcerer',
                'type' => 'sorcerer',
                'description' => 'A practitioner of forbidden magic',
                'strength' => 8,
                'agility' => 9,
                'magic' => 14,
                'defense' => 6,
                'maxHp' => 35,
                'difficulty' => 3,
                'color' => '#9932CC',
                'behavior' => 'ranged',
                'xp' => 35,
                'gold' => 25,
            ],
            [
                'name' => 'Corrupted Knight',
                'type' => 'knight',
                'description' => 'A fallen warrior consumed by darkness',
                'strength' => 14,
                'agility' => 8,
                'magic' => 6,
                'defense' => 12,
                'maxHp' => 60,
                'difficulty' => 3,
                'color' => '#FF4500',
                'behavior' => 'aggressive',
                'xp' => 40,
                'gold' => 35,
            ],
            [
                'name' => 'Shadow Drake',
                'type' => 'dragon',
                'description' => 'A mighty dragon wreathed in shadows',
                'strength' => 18,
                'agility' => 11,
                'magic' => 12,
                'defense' => 14,
                'maxHp' => 100,
                'difficulty' => 4,
                'color' => '#2F4F4F',
                'behavior' => 'aggressive',
                'xp' => 60,
                'gold' => 60,
            ],
        ];

        // Add 5-7 enemies per universe
        $selected = array_slice($enemyTemplates, 0, 5 + (crc32($universeName) % 3));

        return $selected;
    }
}
