<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209040800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dateLimite field to Defi entity';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('defi')) {
            $this->addSql('ALTER TABLE defi ADD date_limite DATE DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('defi')) {
            $this->addSql('ALTER TABLE defi DROP date_limite');
        }
    }
}
