<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create game_run table for persistent game state';
    }

    public function up(Schema $schema): void
    {
        // Create game_run table - simplified for MariaDB compatibility
        $this->addSql('CREATE TABLE IF NOT EXISTS game_run (
            id INT AUTO_INCREMENT NOT NULL,
            personnage_id INT NOT NULL,
            health INT NOT NULL,
            mp INT NOT NULL,
            coins INT NOT NULL,
            kills INT NOT NULL,
            level INT NOT NULL,
            defeated_enemies LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_GAME_RUN_PERSONNAGE (personnage_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS game_run');
    }
}
