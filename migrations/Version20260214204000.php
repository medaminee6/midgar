<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260214204000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create favoris table for user favorites (likes)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE favoris (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            oeuvre_id INT DEFAULT NULL,
            artefact_id INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_FAVORIS_USER (user_id),
            INDEX IDX_FAVORIS_OEUVRE (oeuvre_id),
            INDEX IDX_FAVORIS_ARTEFACT (artefact_id),
            UNIQUE INDEX UNIQ_FAVORIS_USER_OEUVRE (user_id, oeuvre_id),
            UNIQUE INDEX UNIQ_FAVORIS_USER_ARTEFACT (user_id, artefact_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE favoris');
    }
}
