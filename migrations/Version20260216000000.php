<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tags column to content tables: oeuvres, artefacts, personnage, universe';
    }

    public function up(Schema $schema): void
    {
        // Add tags column to oeuvres table
        $this->addSql('ALTER TABLE oeuvres ADD COLUMN tags VARCHAR(255) NOT NULL DEFAULT ""');

        // Add tags column to artefacts table
        $this->addSql('ALTER TABLE artefacts ADD COLUMN tags VARCHAR(255) NOT NULL DEFAULT ""');

        // Add tags column to personnage table
        $this->addSql('ALTER TABLE personnage ADD COLUMN tags VARCHAR(255) NOT NULL DEFAULT ""');

        // Add tags column to universe table
        $this->addSql('ALTER TABLE universe ADD COLUMN tags VARCHAR(255) NOT NULL DEFAULT ""');
    }

    public function down(Schema $schema): void
    {
        // Remove tags column from oeuvres table
        $this->addSql('ALTER TABLE oeuvres DROP COLUMN tags');

        // Remove tags column from artefacts table
        $this->addSql('ALTER TABLE artefacts DROP COLUMN tags');

        // Remove tags column from personnage table
        $this->addSql('ALTER TABLE personnage DROP COLUMN tags');

        // Remove tags column from universe table
        $this->addSql('ALTER TABLE universe DROP COLUMN tags');
    }
}
