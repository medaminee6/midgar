<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create Universe and Personnage tables
 */
final class Version20260209000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Universe and Personnage tables with relationships';
    }

    public function up(Schema $schema): void
    {
        // Create universe table
        $this->addSql('CREATE TABLE IF NOT EXISTS universe (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL UNIQUE,
            genre VARCHAR(100) NOT NULL,
            short_description VARCHAR(500) NOT NULL,
            story_context LONGTEXT NOT NULL,
            themes JSON,
            banner_image LONGBLOB,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY (id),
            UNIQUE KEY uniq_universe_name (name)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create personnage table
        $this->addSql('CREATE TABLE IF NOT EXISTS personnage (
            id INT AUTO_INCREMENT NOT NULL,
            universe_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            class_role VARCHAR(100) NOT NULL,
            history_context LONGTEXT NOT NULL,
            abilities_powers LONGTEXT,
            strength INT,
            agility INT,
            magic INT,
            defense INT,
            portrait_image LONGBLOB,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY (id),
            KEY IDX_PERSONNAGE_UNIVERSE (universe_id),
            CONSTRAINT FK_PERSONNAGE_UNIVERSE FOREIGN KEY (universe_id) REFERENCES universe (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS personnage');
        $this->addSql('DROP TABLE IF EXISTS universe');
    }
}
