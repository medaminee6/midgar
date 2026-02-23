<?php

/**
 * Database Setup Script
 * This script creates all necessary tables for the Symfony application
 * based on the entity definitions.
 */

require __DIR__.'/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

// Database connection
$connectionParams = [
    'dbname' => 'midgar1',
    'user' => 'root',
    'password' => '',
    'host' => '127.0.0.1',
    'driver' => 'pdo_mysql',
];

try {
    $conn = DriverManager::getConnection($connectionParams);
    
    echo "Connected to database successfully.\n";
    
    // SQL statements to create all tables
    $sqlStatements = [
        // Create user table
        "CREATE TABLE IF NOT EXISTS user (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            username VARCHAR(255) NOT NULL,
            is_verified TINYINT(1) DEFAULT 0 NOT NULL,
            avatar_filename VARCHAR(255) DEFAULT NULL,
            google_id VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
            UNIQUE INDEX UNIQ_8D93D649F85E0677 (username),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create universe table
        "CREATE TABLE IF NOT EXISTS universe (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            genre VARCHAR(100) NOT NULL,
            short_description VARCHAR(500) NOT NULL,
            story_context LONGTEXT NOT NULL,
            themes JSON DEFAULT NULL,
            banner_image LONGBLOB DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            UNIQUE INDEX uniq_universe_name (name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create personnage table
        "CREATE TABLE IF NOT EXISTS personnage (
            id INT AUTO_INCREMENT NOT NULL,
            universe_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            class_role VARCHAR(100) NOT NULL,
            history_context LONGTEXT NOT NULL,
            abilities_powers LONGTEXT DEFAULT NULL,
            strength INT DEFAULT NULL,
            agility INT DEFAULT NULL,
            magic INT DEFAULT NULL,
            defense INT DEFAULT NULL,
            portrait_image LONGBLOB DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX IDX_PERSONNAGE_UNIVERSE (universe_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_PERSONNAGE_UNIVERSE FOREIGN KEY (universe_id) REFERENCES universe (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create produit table
        "CREATE TABLE IF NOT EXISTS produit (
            id INT AUTO_INCREMENT NOT NULL,
            nom_produit VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            prix DECIMAL(10, 2) NOT NULL,
            type_produit VARCHAR(100) NOT NULL,
            quantite_disponible INT NOT NULL,
            date_ajout DATETIME NOT NULL,
            image_filename VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create commande table
        "CREATE TABLE IF NOT EXISTS commande (
            id INT AUTO_INCREMENT NOT NULL,
            produit_id INT NOT NULL,
            quantite INT NOT NULL,
            date_commande DATETIME NOT NULL,
            etat VARCHAR(50) NOT NULL DEFAULT 'en_attente',
            acheteur VARCHAR(255) NOT NULL,
            prix_total DECIMAL(10, 2) NOT NULL,
            reference_commande VARCHAR(100) NOT NULL UNIQUE,
            INDEX IDX_COMMANDE_PRODUIT (produit_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_COMMANDE_PRODUIT FOREIGN KEY (produit_id) REFERENCES produit (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create questions table
        "CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT NOT NULL,
            question VARCHAR(500) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create reponses table
        "CREATE TABLE IF NOT EXISTS reponses (
            id INT AUTO_INCREMENT NOT NULL,
            question_id INT NOT NULL,
            `option` VARCHAR(500) NOT NULL,
            tag VARCHAR(100) NOT NULL,
            INDEX IDX_REPONSES_QUESTION (question_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_REPONSES_QUESTION FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create advanced_preferences table
        "CREATE TABLE IF NOT EXISTS advanced_preferences (
            id INT AUTO_INCREMENT NOT NULL,
            free_description LONGTEXT NOT NULL,
            favorite_genre VARCHAR(100) NOT NULL,
            affinity_level INT NOT NULL,
            favorite_themes LONGTEXT NOT NULL,
            custom_tags LONGTEXT NOT NULL,
            user_id VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create defi table
        "CREATE TABLE IF NOT EXISTS defi (
            id INT AUTO_INCREMENT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            type_defi VARCHAR(100) NOT NULL,
            difficulte VARCHAR(50) NOT NULL,
            points INT NOT NULL,
            date_debut DATE NOT NULL,
            date_limite DATE DEFAULT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'actif',
            image_filename VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create oeuvre table
        "CREATE TABLE IF NOT EXISTS oeuvre (
            id INT AUTO_INCREMENT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            type_oeuvre VARCHAR(100) NOT NULL,
            image_filename VARCHAR(255) DEFAULT NULL,
            created_by_id INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_OEUVRE_CREATED_BY (created_by_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_OEUVRE_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create artefact table
        "CREATE TABLE IF NOT EXISTS artefacts (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            type_artefact VARCHAR(100) NOT NULL,
            puissance INT NOT NULL,
            rarete VARCHAR(50) NOT NULL,
            image_filename VARCHAR(255) DEFAULT NULL,
            created_by_id INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_ARTEFACTS_CREATED_BY (created_by_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_ARTEFACTS_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create participation table
        "CREATE TABLE IF NOT EXISTS participation (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            defi_id INT NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'en_cours',
            progres JSON DEFAULT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE DEFAULT NULL,
            score INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_PARTICIPATION_USER (user_id),
            INDEX IDX_PARTICIPATION_DEFI (defi_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_PARTICIPATION_USER FOREIGN KEY (user_id) REFERENCES user (id),
            CONSTRAINT FK_PARTICIPATION_DEFI FOREIGN KEY (defi_id) REFERENCES defi (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create quiz table
        "CREATE TABLE IF NOT EXISTS quiz (
            id INT AUTO_INCREMENT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
        
        // Create quiz_question table (junction table for quiz and questions)
        "CREATE TABLE IF NOT EXISTS quiz_question (
            quiz_id INT NOT NULL,
            question_id INT NOT NULL,
            PRIMARY KEY(quiz_id, question_id),
            CONSTRAINT FK_QUIZ_QUESTION_QUIZ FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE,
            CONSTRAINT FK_QUIZ_QUESTION_QUESTION FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
    ];
    
    // Execute all SQL statements
    foreach ($sqlStatements as $sql) {
        try {
            $conn->executeStatement($sql);
            echo "Table created successfully.\n";
        } catch (\Exception $e) {
            echo "Error creating table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nDatabase setup completed successfully!\n";
    
} catch (\Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}