<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create all missing tables and fix schema for Quiz and Shop modules
 */
final class Version20260208220200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all missing tables for Quiz and Shop modules with proper Doctrine conventions';
    }

    public function up(Schema $schema): void
    {
        // Drop foreign key constraint first using actual constraint name
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY commande_ibfk_1');
        
        // Drop old indexes
        $this->addSql('ALTER TABLE commande DROP INDEX IF EXISTS idx_reference');
        $this->addSql('ALTER TABLE commande DROP INDEX IF EXISTS idx_produit_id');
        $this->addSql('ALTER TABLE commande DROP INDEX IF EXISTS reference_commande');

        // Recreate foreign key with proper Doctrine naming convention
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE commande ADD UNIQUE KEY UNIQ_6EEAA67DB6E0FD02 (reference_commande)');
        $this->addSql('ALTER TABLE commande ADD INDEX IDX_6EEAA67DF347EFB (produit_id)');

        // Create questions table
        $this->addSql('CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT NOT NULL,
            question VARCHAR(500) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create reponses table
        $this->addSql('CREATE TABLE IF NOT EXISTS reponses (
            id INT AUTO_INCREMENT NOT NULL,
            question_id INT NOT NULL,
            option VARCHAR(500) NOT NULL,
            tag VARCHAR(100) NOT NULL,
            PRIMARY KEY (id),
            CONSTRAINT FK_REPONSES_QUESTION FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE,
            INDEX IDX_REPONSES_QUESTION (question_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create advanced_preferences table
        $this->addSql('CREATE TABLE IF NOT EXISTS advanced_preferences (
            id INT AUTO_INCREMENT NOT NULL,
            free_description LONGTEXT NOT NULL,
            favorite_genre VARCHAR(100) NOT NULL,
            affinity_level INT NOT NULL,
            favorite_themes LONGTEXT NOT NULL,
            custom_tags LONGTEXT NOT NULL,
            user_id VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Rollback is not recommended for this integrated schema
        $this->addSql('DROP TABLE IF EXISTS reponses');
        $this->addSql('DROP TABLE IF EXISTS questions');
        $this->addSql('DROP TABLE IF EXISTS advanced_preferences');
    }
}
