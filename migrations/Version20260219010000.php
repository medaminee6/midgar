<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product creator relation for automation email routing.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD creator_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_PRODUIT_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_PRODUIT_CREATED_BY ON produit (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_PRODUIT_CREATED_BY');
        $this->addSql('DROP INDEX IDX_PRODUIT_CREATED_BY ON produit');
        $this->addSql('ALTER TABLE produit DROP created_by_id');
        $this->addSql('ALTER TABLE produit DROP creator_email');
    }
}
