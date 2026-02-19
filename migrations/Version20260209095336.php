<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209095336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // This migration is handled by Version20260215000000
        // Skip all operations to avoid conflicts
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artefacts DROP FOREIGN KEY FK_5E5C86F3B03A8386');
        $this->addSql('ALTER TABLE artefacts DROP FOREIGN KEY FK_5E5C86F3B03A8386');
        $this->addSql('ALTER TABLE artefacts ADD created_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_5e5c86f3b03a8386 ON artefacts');
        $this->addSql('CREATE INDEX FK_artefacts_created_by_id ON artefacts (created_by_id)');
        $this->addSql('ALTER TABLE artefacts ADD CONSTRAINT FK_5E5C86F3B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande CHANGE etat etat VARCHAR(50) DEFAULT \'en_attente\' NOT NULL');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3EB03A8386');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3EB03A8386');
        $this->addSql('ALTER TABLE oeuvres ADD created_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_oeuvres_created_by_id FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('DROP INDEX idx_413eee3eb03a8386 ON oeuvres');
        $this->addSql('CREATE INDEX FK_oeuvres_created_by_id ON oeuvres (created_by_id)');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_413EEE3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE personnage DROP FOREIGN KEY FK_6AEA486D5CD9AF2');
        $this->addSql('ALTER TABLE personnage CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_6aea486d5cd9af2 ON personnage');
        $this->addSql('CREATE INDEX IDX_PERSONNAGE_UNIVERSE ON personnage (universe_id)');
        $this->addSql('ALTER TABLE personnage ADD CONSTRAINT FK_6AEA486D5CD9AF2 FOREIGN KEY (universe_id) REFERENCES universe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponses DROP FOREIGN KEY FK_1E512EC61E27F6BF');
        $this->addSql('ALTER TABLE reponses DROP FOREIGN KEY FK_1E512EC61E27F6BF');
        $this->addSql('ALTER TABLE reponses ADD CONSTRAINT FK_REPONSES_QUESTION FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_1e512ec61e27f6bf ON reponses');
        $this->addSql('CREATE INDEX IDX_REPONSES_QUESTION ON reponses (question_id)');
        $this->addSql('ALTER TABLE reponses ADD CONSTRAINT FK_1E512EC61E27F6BF FOREIGN KEY (question_id) REFERENCES questions (id)');
        $this->addSql('ALTER TABLE universe CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX name ON universe (name)');
        $this->addSql('ALTER TABLE `user` CHANGE is_verified is_verified TINYINT(1) NOT NULL');
    }
}
