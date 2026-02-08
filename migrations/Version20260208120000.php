<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add updated_at to defi for VichUploader fileNameProperty change detection.
 */
final class Version20260208120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add updated_at column to defi table for image upload';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE defi ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE defi DROP updated_at');
    }
}
