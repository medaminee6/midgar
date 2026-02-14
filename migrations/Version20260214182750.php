<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260214182750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE oeuvres ADD universe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_413EEE3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_413EEE3E5CD9AF2 FOREIGN KEY (universe_id) REFERENCES universe (id)');
        $this->addSql('CREATE INDEX IDX_413EEE3EB03A8386 ON oeuvres (created_by_id)');
        $this->addSql('CREATE INDEX IDX_413EEE3E5CD9AF2 ON oeuvres (universe_id)');
        $this->addSql('ALTER TABLE personnage ADD CONSTRAINT FK_6AEA486D5CD9AF2 FOREIGN KEY (universe_id) REFERENCES universe (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_6AEA486D5CD9AF2 ON personnage (universe_id)');
        $this->addSql('ALTER TABLE reponses ADD CONSTRAINT FK_1E512EC61E27F6BF FOREIGN KEY (question_id) REFERENCES questions (id)');
        $this->addSql('CREATE INDEX IDX_1E512EC61E27F6BF ON reponses (question_id)');
        $this->addSql('ALTER TABLE universe CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE is_verified is_verified TINYINT(1) DEFAULT 0 NOT NULL, CHANGE auth_provider auth_provider VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON user (google_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3EB03A8386');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3E5CD9AF2');
        $this->addSql('DROP INDEX IDX_413EEE3EB03A8386 ON oeuvres');
        $this->addSql('DROP INDEX IDX_413EEE3E5CD9AF2 ON oeuvres');
        $this->addSql('ALTER TABLE oeuvres DROP universe_id');
        $this->addSql('ALTER TABLE personnage DROP FOREIGN KEY FK_6AEA486D5CD9AF2');
        $this->addSql('DROP INDEX IDX_6AEA486D5CD9AF2 ON personnage');
        $this->addSql('ALTER TABLE reponses DROP FOREIGN KEY FK_1E512EC61E27F6BF');
        $this->addSql('DROP INDEX IDX_1E512EC61E27F6BF ON reponses');
        $this->addSql('ALTER TABLE universe CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX UNIQ_8D93D64976F5C865 ON `user`');
        $this->addSql('ALTER TABLE `user` CHANGE is_verified is_verified TINYINT(1) NOT NULL, CHANGE auth_provider auth_provider VARCHAR(50) DEFAULT \'local\' NOT NULL');
    }
}
