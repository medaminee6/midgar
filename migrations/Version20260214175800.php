<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260214175800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create commentaires table for Oeuvres and Artefacts comments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE commentaires (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            oeuvre_id INT DEFAULT NULL,
            artefact_id INT DEFAULT NULL,
            contenu LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE commentaires');
    }
}
