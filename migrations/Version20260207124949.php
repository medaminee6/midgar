<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207124949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD email VARCHAR(180) NOT NULL, ADD password VARCHAR(255) NOT NULL, ADD role VARCHAR(20) NOT NULL, ADD nom VARCHAR(255) NOT NULL, ADD prenom VARCHAR(255) NOT NULL, ADD username VARCHAR(255) NOT NULL, ADD photo_profil VARCHAR(255) DEFAULT NULL, ADD bio LONGTEXT DEFAULT NULL, ADD is_blocked TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON `user`');
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP email, DROP password, DROP role, DROP nom, DROP prenom, DROP username, DROP photo_profil, DROP bio, DROP is_blocked');
    }
}
