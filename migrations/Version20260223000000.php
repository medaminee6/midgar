<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create game_run table for persistent game state';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_run (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, personnage_id INTEGER NOT NULL, health INTEGER NOT NULL, mp INTEGER NOT NULL, coins INTEGER NOT NULL, kills INTEGER NOT NULL, level INTEGER NOT NULL, defeated_enemies CLOB NOT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , FOREIGN KEY (personnage_id) REFERENCES personnage (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_3B3E5B2F5E315342 ON game_run (personnage_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE game_run');
    }
}