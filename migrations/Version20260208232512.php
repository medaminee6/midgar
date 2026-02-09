<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208232512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE defi (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, theme VARCHAR(255) NOT NULL, image_cover VARCHAR(255) DEFAULT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, statut VARCHAR(255) NOT NULL, createur_id INT NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, date_soumission DATE NOT NULL, statut VARCHAR(255) NOT NULL, user_id INT NOT NULL, artwork_id INT DEFAULT NULL, image_file_name VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, defi_id INT NOT NULL, INDEX IDX_AB55E24F73F00F27 (defi_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F73F00F27 FOREIGN KEY (defi_id) REFERENCES defi (id)');
        $this->addSql('ALTER TABLE artefacts DROP FOREIGN KEY FK_artefacts_created_by_id');
        $this->addSql('ALTER TABLE artefacts DROP FOREIGN KEY FK_artefacts_created_by_id');
        $this->addSql('ALTER TABLE artefacts DROP created_by');
        $this->addSql('ALTER TABLE artefacts ADD CONSTRAINT FK_5E5C86F3B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX fk_artefacts_created_by_id ON artefacts');
        $this->addSql('CREATE INDEX IDX_5E5C86F3B03A8386 ON artefacts (created_by_id)');
        $this->addSql('ALTER TABLE artefacts ADD CONSTRAINT FK_artefacts_created_by_id FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_oeuvres_created_by_id');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_oeuvres_created_by_id');
        $this->addSql('ALTER TABLE oeuvres DROP created_by');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_413EEE3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX fk_oeuvres_created_by_id ON oeuvres');
        $this->addSql('CREATE INDEX IDX_413EEE3EB03A8386 ON oeuvres (created_by_id)');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_oeuvres_created_by_id FOREIGN KEY (created_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F73F00F27');
        $this->addSql('DROP TABLE defi');
        $this->addSql('DROP TABLE participation');
        $this->addSql('ALTER TABLE artefacts DROP FOREIGN KEY FK_5E5C86F3B03A8386');
        $this->addSql('ALTER TABLE artefacts DROP FOREIGN KEY FK_5E5C86F3B03A8386');
        $this->addSql('ALTER TABLE artefacts ADD created_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE artefacts ADD CONSTRAINT FK_artefacts_created_by_id FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('DROP INDEX idx_5e5c86f3b03a8386 ON artefacts');
        $this->addSql('CREATE INDEX FK_artefacts_created_by_id ON artefacts (created_by_id)');
        $this->addSql('ALTER TABLE artefacts ADD CONSTRAINT FK_5E5C86F3B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3EB03A8386');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3EB03A8386');
        $this->addSql('ALTER TABLE oeuvres ADD created_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_oeuvres_created_by_id FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('DROP INDEX idx_413eee3eb03a8386 ON oeuvres');
        $this->addSql('CREATE INDEX FK_oeuvres_created_by_id ON oeuvres (created_by_id)');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_413EEE3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }
}
