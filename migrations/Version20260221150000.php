<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create Enemy table for game battles
 */
final class Version20260221150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create enemy table for game integration';
    }

    public function up(Schema $schema): void
    {
        // Create enemy table - SQLite compatible
        $this->addSql('CREATE TABLE IF NOT EXISTS enemy (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            enemy_type VARCHAR(100) NOT NULL,
            description LONGTEXT,
            strength INTEGER DEFAULT 10,
            agility INTEGER DEFAULT 10,
            magic INTEGER DEFAULT 0,
            defense INTEGER DEFAULT 5,
            max_hp INTEGER DEFAULT 30,
            difficulty_tier INTEGER DEFAULT 1,
            color_hex VARCHAR(7) DEFAULT "#888888",
            portrait_image BLOB,
            behavior_type VARCHAR(50) DEFAULT "patrol",
            loot_xp INTEGER DEFAULT 10,
            loot_gold INTEGER DEFAULT 0,
            universe_id INTEGER NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (universe_id) REFERENCES universe (id) ON DELETE CASCADE
        )');
        
        $this->addSql('CREATE INDEX IDX_44F3991A5CD9AF2 ON enemy (universe_id)');
        $this->addSql('CREATE INDEX IDX_44F3991A_enemy_type ON enemy (enemy_type)');
        $this->addSql('CREATE INDEX IDX_44F3991A_difficulty ON enemy (difficulty_tier)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS enemy');
    }
}
