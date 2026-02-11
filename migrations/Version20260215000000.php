<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all tables for User, Oeuvre, Artefact and related entities';
    }

    public function up(Schema $schema): void
    {
        // Create user table
        $this->addSql('CREATE TABLE IF NOT EXISTS `user` (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            username VARCHAR(255) NOT NULL,
            is_verified TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            role VARCHAR(20) NOT NULL,
            nom VARCHAR(255) NOT NULL,
            prenom VARCHAR(255) NOT NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            bio LONGTEXT DEFAULT NULL,
            is_blocked TINYINT(1) DEFAULT 0 NOT NULL,
            UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
            UNIQUE INDEX UNIQ_8D93D649F85E0677 (username),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create universe table
        $this->addSql('CREATE TABLE IF NOT EXISTS universe (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            genre VARCHAR(100) NOT NULL,
            short_description VARCHAR(500) NOT NULL,
            story_context LONGTEXT NOT NULL,
            themes JSON DEFAULT NULL,
            banner_image LONGBLOB DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX uniq_universe_name (name),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create defi table
        $this->addSql('CREATE TABLE IF NOT EXISTS defi (
            id INT AUTO_INCREMENT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description VARCHAR(255) NOT NULL,
            theme VARCHAR(255) NOT NULL,
            image_cover VARCHAR(255) DEFAULT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            statut VARCHAR(255) NOT NULL,
            createur_id INT NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            date_limite DATE DEFAULT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create participation table
        $this->addSql('CREATE TABLE IF NOT EXISTS participation (
            id INT AUTO_INCREMENT NOT NULL,
            defi_id INT NOT NULL,
            description VARCHAR(255) NOT NULL,
            date_soumission DATE NOT NULL,
            statut VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            artwork_id INT DEFAULT NULL,
            image_file_name VARCHAR(255) DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX IDX_AB55E24F73F00F27 (defi_id),
            PRIMARY KEY (id),
            CONSTRAINT FK_AB55E24F73F00F27 FOREIGN KEY (defi_id) REFERENCES defi (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create oeuvre table
        $this->addSql('CREATE TABLE IF NOT EXISTS oeuvres (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            description LONGTEXT NOT NULL,
            date_publication DATE DEFAULT NULL,
            image_url VARCHAR(500) DEFAULT NULL,
            author VARCHAR(255) DEFAULT NULL,
            created_by_id INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            universe_id INT DEFAULT NULL,
            INDEX IDX_OEUVRES_UNIVERSE (universe_id),
            INDEX IDX_OEUVRES_CREATED_BY (created_by_id),
            PRIMARY KEY (id),
            CONSTRAINT FK_OEUVRES_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL,
            CONSTRAINT FK_OEUVRES_UNIVERSE FOREIGN KEY (universe_id) REFERENCES universe (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create artefact table
        $this->addSql('CREATE TABLE IF NOT EXISTS artefacts (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            universe VARCHAR(255) NOT NULL,
            origins LONGTEXT NOT NULL,
            powers LONGTEXT NOT NULL,
            rarity VARCHAR(50) NOT NULL,
            image_url VARCHAR(500) DEFAULT NULL,
            created_by_id INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_ARTEFACTS_CREATED_BY (created_by_id),
            PRIMARY KEY (id),
            CONSTRAINT FK_ARTEFACTS_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create personnage table
        $this->addSql('CREATE TABLE IF NOT EXISTS personnage (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            role VARCHAR(100) NOT NULL,
            description LONGTEXT NOT NULL,
            univers_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_6AEA486D5CD9AF2 (univers_id),
            PRIMARY KEY (id),
            CONSTRAINT FK_PERSONNAGE_UNIVERS FOREIGN KEY (univers_id) REFERENCES universe (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create commande table
        $this->addSql('CREATE TABLE IF NOT EXISTS commande (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            produit_id INT NOT NULL,
            quantite INT NOT NULL,
            date_commande DATETIME NOT NULL,
            etat VARCHAR(50) NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create produit table
        $this->addSql('CREATE TABLE IF NOT EXISTS produit (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            prix DECIMAL(10,2) NOT NULL,
            stock INT NOT NULL,
            image_url VARCHAR(255) DEFAULT NULL,
            univers_id INT DEFAULT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create questions table
        $this->addSql('CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT NOT NULL,
            question LONGTEXT NOT NULL,
            quiz_id INT NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create reponses table
        $this->addSql('CREATE TABLE IF NOT EXISTS reponses (
            id INT AUTO_INCREMENT NOT NULL,
            reponse LONGTEXT NOT NULL,
            est_correcte TINYINT(1) NOT NULL,
            question_id INT NOT NULL,
            INDEX IDX_1E512EC61E27F6BF (question_id),
            PRIMARY KEY (id),
            CONSTRAINT FK_1E512EC61E27F6BF FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS reponses');
        $this->addSql('DROP TABLE IF EXISTS questions');
        $this->addSql('DROP TABLE IF EXISTS produit');
        $this->addSql('DROP TABLE IF EXISTS commande');
        $this->addSql('DROP TABLE IF EXISTS personnage');
        $this->addSql('DROP TABLE IF EXISTS artefacts');
        $this->addSql('DROP TABLE IF EXISTS oeuvres');
        $this->addSql('DROP TABLE IF EXISTS participation');
        $this->addSql('DROP TABLE IF EXISTS defi');
        $this->addSql('DROP TABLE IF EXISTS universe');
        $this->addSql('DROP TABLE IF EXISTS `user`');
    }
}
