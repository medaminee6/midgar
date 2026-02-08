<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208CreateUniversePersonnage extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create universe and personnage tables with relations';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE universe (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, genre VARCHAR(100) NOT NULL, shortDescription VARCHAR(500) NOT NULL, storyContext LONGTEXT NOT NULL, themes JSON DEFAULT NULL, bannerImage LONGBLOB DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, UNIQUE INDEX uniq_universe_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personnage (id INT AUTO_INCREMENT NOT NULL, universe_id INT NOT NULL, name VARCHAR(255) NOT NULL, classRole VARCHAR(100) NOT NULL, historyContext LONGTEXT NOT NULL, abilitiesPowers LONGTEXT DEFAULT NULL, portraitImage LONGBLOB DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, INDEX IDX_PERSONNAGE_UNIVERSE (universe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE personnage ADD CONSTRAINT FK_PERSONNAGE_UNIVERSE FOREIGN KEY (universe_id) REFERENCES universe (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnage DROP FOREIGN KEY FK_PERSONNAGE_UNIVERSE');
        $this->addSql('DROP TABLE personnage');
        $this->addSql('DROP TABLE universe');
    }
}
