<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Participation: image upload (peinture/dessin) + artworkId nullable.
 */
final class Version20260208140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Participation image upload and nullable artwork_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participation ADD image_file_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE participation ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE participation ALTER artwork_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participation DROP image_file_name');
        $this->addSql('ALTER TABLE participation DROP updated_at');
        $this->addSql('ALTER TABLE participation ALTER artwork_id SET NOT NULL');
    }
}
