<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213190628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE oeuvres DROP created_by');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_413EEE3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX fk_oeuvres_created_by_id ON oeuvres');
        $this->addSql('CREATE INDEX IDX_413EEE3EB03A8386 ON oeuvres (created_by_id)');
        $this->addSql('ALTER TABLE personnage DROP FOREIGN KEY FK_PERSONNAGE_UNIVERSE');
        $this->addSql('ALTER TABLE personnage CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_personnage_universe ON personnage');
        $this->addSql('CREATE INDEX IDX_6AEA486D5CD9AF2 ON personnage (universe_id)');
        $this->addSql('ALTER TABLE personnage ADD CONSTRAINT FK_PERSONNAGE_UNIVERSE FOREIGN KEY (universe_id) REFERENCES universe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponses DROP FOREIGN KEY FK_REPONSES_QUESTION');
        $this->addSql('ALTER TABLE reponses DROP FOREIGN KEY FK_REPONSES_QUESTION');
        $this->addSql('ALTER TABLE reponses ADD CONSTRAINT FK_1E512EC61E27F6BF FOREIGN KEY (question_id) REFERENCES questions (id)');
        $this->addSql('DROP INDEX idx_reponses_question ON reponses');
        $this->addSql('CREATE INDEX IDX_1E512EC61E27F6BF ON reponses (question_id)');
        $this->addSql('ALTER TABLE reponses ADD CONSTRAINT FK_REPONSES_QUESTION FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX name ON universe');
        $this->addSql('ALTER TABLE universe CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user ADD google_id VARCHAR(255) DEFAULT NULL, ADD auth_provider VARCHAR(50) NOT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL, CHANGE is_verified is_verified TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON user (google_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3EB03A8386');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3EB03A8386');
        $this->addSql('ALTER TABLE oeuvres ADD created_by VARCHAR(255) DEFAULT NULL');
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
        $this->addSql('DROP INDEX UNIQ_8D93D64976F5C865 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP google_id, DROP auth_provider, CHANGE password password VARCHAR(255) NOT NULL, CHANGE is_verified is_verified TINYINT(1) NOT NULL');
    }
}
