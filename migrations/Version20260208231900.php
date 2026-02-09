<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208231900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_by_id FK to artefacts and oeuvres linking to user table';
    }

    public function up(Schema $schema): void
    {
        // add new FK columns (keep legacy created_by varchar intact)
        $this->addSql('ALTER TABLE artefacts ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE oeuvres ADD created_by_id INT DEFAULT NULL');

        // add foreign key constraints
        $this->addSql('ALTER TABLE artefacts ADD CONSTRAINT FK_artefacts_created_by_id FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_oeuvres_created_by_id FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artefacts DROP FOREIGN KEY FK_artefacts_created_by_id');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_oeuvres_created_by_id');
        $this->addSql('ALTER TABLE artefacts DROP COLUMN created_by_id');
        $this->addSql('ALTER TABLE oeuvres DROP COLUMN created_by_id');
    }
}
