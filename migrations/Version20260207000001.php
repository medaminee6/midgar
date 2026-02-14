<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add createdBy field to oeuvres and artefacts tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvres ADD created_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE artefacts ADD created_by VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvres DROP created_by');
        $this->addSql('ALTER TABLE artefacts DROP created_by');
    }
}
