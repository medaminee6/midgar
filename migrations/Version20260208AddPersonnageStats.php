<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208AddPersonnageStats extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stats fields (strength, agility, magic, defense) to personnage table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnage ADD strength INT DEFAULT NULL');
        $this->addSql('ALTER TABLE personnage ADD agility INT DEFAULT NULL');
        $this->addSql('ALTER TABLE personnage ADD magic INT DEFAULT NULL');
        $this->addSql('ALTER TABLE personnage ADD defense INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnage DROP COLUMN strength');
        $this->addSql('ALTER TABLE personnage DROP COLUMN agility');
        $this->addSql('ALTER TABLE personnage DROP COLUMN magic');
        $this->addSql('ALTER TABLE personnage DROP COLUMN defense');
    }
}
