<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create oeuvre and artefact tables';
    }

    public function up(Schema $schema): void
    {
        // Create oeuvre table
        $this->addSql('CREATE TABLE IF NOT EXISTS oeuvre (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, description LONGTEXT NOT NULL, date_pub DATE DEFAULT NULL, image_url VARCHAR(500) DEFAULT NULL, author VARCHAR(255) DEFAULT NULL, created_by_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_30A25A6412469DE2 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $oeuvreFkExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'FK_30A25A6412469DE2'"
        );
        if ($oeuvreFkExists === 0 && $schema->hasTable('user')) {
            $this->addSql('ALTER TABLE oeuvre ADD CONSTRAINT FK_30A25A6412469DE2 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        }
        
        // Create artefact table
        $this->addSql('CREATE TABLE IF NOT EXISTS artefact (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, universe VARCHAR(255) NOT NULL, origins LONGTEXT NOT NULL, powers LONGTEXT NOT NULL, rarity VARCHAR(50) NOT NULL, image_url VARCHAR(500) DEFAULT NULL, created_by_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_8B4AA47B12469DE2 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $artefactFkExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'FK_8B4AA47B12469DE2'"
        );
        if ($artefactFkExists === 0 && $schema->hasTable('user')) {
            $this->addSql('ALTER TABLE artefact ADD CONSTRAINT FK_8B4AA47B12469DE2 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE oeuvre');
        $this->addSql('DROP TABLE artefact');
    }
}
