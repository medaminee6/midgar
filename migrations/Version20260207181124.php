<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207181124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename column thème to theme';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE defi CHANGE COLUMN `thème` `theme` VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE defi CHANGE COLUMN `theme` `thème` VARCHAR(255) NOT NULL');
    }
}
