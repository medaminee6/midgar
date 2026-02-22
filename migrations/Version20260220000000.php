<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Add tag field to Oeuvre, Artefact, Personnage, and Universe
 */
final class Version20260220000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tag field to Oeuvre, Artefact, Personnage, and Universe entities';
    }

    public function up(Schema $schema): void
    {
        // Add tag column to oeuvres table
        $this->addSql('ALTER TABLE oeuvres ADD tag VARCHAR(255) NOT NULL');
        
        // Add tag column to artefacts table
        $this->addSql('ALTER TABLE artefacts ADD tag VARCHAR(255) NOT NULL');
        
        // Add tag column to personnage table
        $this->addSql('ALTER TABLE personnage ADD tag VARCHAR(255) NOT NULL');
        
        // Add tag column to universe table
        $this->addSql('ALTER TABLE universe ADD tag VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove tag column from oeuvres table
        $this->addSql('ALTER TABLE oeuvres DROP tag');
        
        // Remove tag column from artefacts table
        $this->addSql('ALTER TABLE artefacts DROP tag');
        
        // Remove tag column from personnage table
        $this->addSql('ALTER TABLE personnage DROP tag');
        
        // Remove tag column from universe table
        $this->addSql('ALTER TABLE universe DROP tag');
    }
}
