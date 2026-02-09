<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create Defi and Participation tables
 */
final class Version20260210000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Defi and Participation tables';
    }

    public function up(Schema $schema): void
    {
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
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
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
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_PARTICIPATION_DEFI (defi_id),
            PRIMARY KEY (id),
            CONSTRAINT FK_PARTICIPATION_DEFI FOREIGN KEY (defi_id) REFERENCES defi (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS participation');
        $this->addSql('DROP TABLE IF EXISTS defi');
    }
}
