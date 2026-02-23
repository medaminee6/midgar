<?php

namespace App\DataFixtures;

use App\Entity\Universe;
use App\Entity\Personnage;
use App\Entity\Enemy;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class InitialDataFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create test user
        $user = new User();
        $user->setEmail('player@example.com');
        $user->setUsername('TestPlayer');
        $user->setNom('Player');
        $user->setPrenom('Test');
        $user->setRole('ROLE_USER');
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'password123'
        );
        $user->setPassword($hashedPassword);
        $user->setAvatar('/avatars/default.png');
        $manager->persist($user);
        $manager->flush();

        // Create universes
        $universes = [
            [
                'name' => 'Final Fantasy VII',
                'genre' => 'JRPG/Sci-Fi',
                'description' => 'The world of Midgar awaits',
                'story' => 'Cloud Strife and his companions fight against the evil Shinra Corporation in a world powered by energy harvested from the planet itself.',
                'themes' => ['science-fiction', 'steampunk', 'environmentalism', 'romance'],
            ],
            [
                'name' => 'Lord of the Rings',
                'genre' => 'Fantasy/Adventure',
                'description' => 'A tale of hobbits, dwarves, and elves',
                'story' => 'The Lord of the Rings follows the quest of hobbits to destroy the One Ring in the fires of Mount Doom.',
                'themes' => ['fantasy', 'adventure', 'heroism', 'friendship'],
            ],
            [
                'name' => 'The Witcher',
                'genre' => 'Dark Fantasy',
                'description' => 'Monsters, magic, and moral ambiguity',
                'story' => 'Geralt of Rivia, a professional monster hunter known as a witcher, navigates a world of magic, politics, and dangerous creatures.',
                'themes' => ['dark-fantasy', 'witchcraft', 'monster-hunting', 'politics'],
            ],
            [
                'name' => 'Dragon Age',
                'genre' => 'Fantasy/RPG',
                'description' => 'A land of magic and dragons',
                'story' => 'In the land of Thedas, darkspawn threaten the world, and only the Grey Wardens stand between civilization and destruction.',
                'themes' => ['fantasy', 'dragons', 'war', 'magic'],
            ],
        ];

        $savedUniverses = [];
        foreach ($universes as $univData) {
            $univ = new Universe();
            $univ->setName($univData['name']);
            $univ->setGenre($univData['genre']);
            $univ->setShortDescription($univData['description']);
            $univ->setStoryContext($univData['story']);
            $univ->setThemes($univData['themes']);
            $manager->persist($univ);
            $savedUniverses[] = $univ;
        }
        $manager->flush();

        // Create personnages for first universe
        $personnages = [
            [
                'universe' => $savedUniverses[0],
                'name' => 'Cloud Strife',
                'classRole' => 'Soldier/Swordsman',
                'history' => 'A former member of Shinra\'s elite SOLDIER unit with mysterious origins. Cloud now fights to save the planet from environmental destruction.',
                'strength' => 18,
                'agility' => 14,
                'magic' => 10,
                'defense' => 12,
            ],
            [
                'universe' => $savedUniverses[0],
                'name' => 'Aerith Gainsborough',
                'classRole' => 'Cleric/Mage',
                'history' => 'The last of the Cetra, an ancient race with the power to communicate with the planet. Aerith fights to protect all living things.',
                'strength' => 8,
                'agility' => 12,
                'magic' => 18,
                'defense' => 10,
            ],
            [
                'universe' => $savedUniverses[1],
                'name' => 'Aragorn',
                'classRole' => 'Ranger/Warrior',
                'history' => 'The rightful heir to the kingdoms of Arnor and Gondor. Aragorn leads the free peoples against the dark lord Sauron.',
                'strength' => 17,
                'agility' => 15,
                'magic' => 6,
                'defense' => 14,
            ],
            [
                'universe' => $savedUniverses[1],
                'name' => 'Frodo Baggins',
                'classRole' => 'Hobbit/Rogue',
                'history' => 'A small hobbit from the Shire chosen to bear the One Ring on a perilous journey to Mount Doom.',
                'strength' => 6,
                'agility' => 14,
                'magic' => 4,
                'defense' => 8,
            ],
            [
                'universe' => $savedUniverses[2],
                'name' => 'Geralt of Rivia',
                'classRole' => 'Witcher/Monster Hunter',
                'history' => 'A mutant monster hunter with years of experience. Geralt uses signs, potions, and his exceptional sword skills to survive.',
                'strength' => 16,
                'agility' => 17,
                'magic' => 12,
                'defense' => 13,
            ],
            [
                'universe' => $savedUniverses[3],
                'name' => 'The Warden',
                'classRole' => 'Grey Warden/Warrior',
                'history' => 'Recruited into the legendary Grey Wardens to fight against the darkspawn plague consuming the land of Thedas.',
                'strength' => 15,
                'agility' => 13,
                'magic' => 11,
                'defense' => 14,
            ],
        ];

        foreach ($personnages as $persoData) {
            $perso = new Personnage();
            $perso->setName($persoData['name']);
            $perso->setClassRole($persoData['classRole']);
            $perso->setHistoryContext($persoData['history']);
            $perso->setStrength($persoData['strength']);
            $perso->setAgility($persoData['agility']);
            $perso->setMagic($persoData['magic']);
            $perso->setDefense($persoData['defense']);
            $perso->setUniverse($persoData['universe']);
            $manager->persist($perso);
        }

        $manager->flush();

        // Create enemies for each universe
        foreach ($savedUniverses as $universe) {
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
        
        $enemyTemplates = [
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
                'behavior' => 'patrol',
                'xp' => 12,
                'gold' => 8,
            ],
            [
                'name' => 'Dark Wolf',
                'type' => 'beast',
                'description' => 'A ferocious wolf from the shadows',
                'strength' => 8,
                'agility' => 14,
                'magic' => 0,
                'defense' => 4,
                'maxHp' => 25,
                'difficulty' => 2,
                'color' => '#4A4A4A',
                'behavior' => 'chase',
                'xp' => 18,
                'gold' => 12,
            ],
            [
                'name' => 'Orc Warrior',
                'type' => 'orc',
                'description' => 'A brutal fighter from the dark lands',
                'strength' => 12,
                'agility' => 6,
                'magic' => 0,
                'defense' => 8,
                'maxHp' => 40,
                'difficulty' => 3,
                'color' => '#228B22',
                'behavior' => 'patrol',
                'xp' => 30,
                'gold' => 25,
            ],
            [
                'name' => 'Shadow Mage',
                'type' => 'mage',
                'description' => 'A sorcerer wielding dark magic',
                'strength' => 4,
                'agility' => 8,
                'magic' => 15,
                'defense' => 3,
                'maxHp' => 20,
                'difficulty' => 3,
                'color' => '#9400D3',
                'behavior' => 'patrol',
                'xp' => 35,
                'gold' => 30,
            ],
        ];
        
        $count = 5 + (crc32($universeName) % 3);
        return array_slice($enemyTemplates, 0, $count);
    }
}
