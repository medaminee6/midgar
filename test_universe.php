#!/usr/bin/env php
<?php
require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    // Create a simple test script to verify Entity persistence works

    $entityManager = $context['object'];
    
    // Test Create Universe
    $universe = new \App\Entity\Universe();
    $universe->setName('Test Universe');
    $universe->setGenre('Fantasy');
    $universe->setShortDescription('A test universe for Doctrine integration');
    $universe->setStoryContext('This is a test story context for the universe');
    $universe->setThemes(['magic', 'adventure']);

    $entityManager->persist($universe);
    $entityManager->flush();
    
    echo "[SUCCESS] Universe created with ID: " . $universe->getId() . "\n";

    // Test Create Personnage
    $personnage = new \App\Entity\Personnage();
    $personnage->setName('Test Character');
    $personnage->setClassRole('Mage');
    $personnage->setHistoryContext('A test character in the test universe');
    $personnage->setUniverse($universe);
    $personnage->setStrength(50);
    $personnage->setAgility(60);
    $personnage->setMagic(90);
    $personnage->setDefense(40);

    $entityManager->persist($personnage);
    $entityManager->flush();
    
    echo "[SUCCESS] Personnage created with ID: " . $personnage->getId() . "\n";
    echo "[SUCCESS] Personnage linked to Universe: " . $personnage->getUniverse()->getName() . "\n";

    // Verify relationships work
    $universeRepo = $entityManager->getRepository(\App\Entity\Universe::class);
    $retrievedUniverse = $universeRepo->find($universe->getId());
    
    if ($retrievedUniverse) {
        echo "[SUCCESS] Universe retrieved: " . $retrievedUniverse->getName() . "\n";
        echo "[SUCCESS] Personnages in universe: " . count($retrievedUniverse->getPersonnages()) . "\n";
    }
};
