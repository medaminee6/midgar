<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209074835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS advanced_preferences (id INT AUTO_INCREMENT NOT NULL, free_description LONGTEXT NOT NULL, favorite_genre VARCHAR(100) NOT NULL, affinity_level INT NOT NULL, favorite_themes LONGTEXT NOT NULL, custom_tags LONGTEXT NOT NULL, user_id VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS commande (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, date_commande DATETIME NOT NULL, etat VARCHAR(50) NOT NULL, acheteur VARCHAR(255) NOT NULL, prix_total NUMERIC(10, 2) NOT NULL, reference_commande VARCHAR(100) NOT NULL, produit_id INT NOT NULL, UNIQUE INDEX UNIQ_6EEAA67DB6E0FD02 (reference_commande), INDEX IDX_6EEAA67DF347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS personnage (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, class_role VARCHAR(100) NOT NULL, history_context LONGTEXT NOT NULL, abilities_powers LONGTEXT DEFAULT NULL, strength INT DEFAULT NULL, agility INT DEFAULT NULL, magic INT DEFAULT NULL, defense INT DEFAULT NULL, portrait_image LONGBLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, universe_id INT NOT NULL, INDEX IDX_6AEA486D5CD9AF2 (universe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS produit (id INT AUTO_INCREMENT NOT NULL, nom_produit VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, prix NUMERIC(10, 2) NOT NULL, type_produit VARCHAR(100) NOT NULL, quantite_disponible INT NOT NULL, date_ajout DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS questions (id INT AUTO_INCREMENT NOT NULL, question VARCHAR(500) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS reponses (id INT AUTO_INCREMENT NOT NULL, `option` VARCHAR(500) NOT NULL, tag VARCHAR(100) NOT NULL, question_id INT NOT NULL, INDEX IDX_1E512EC61E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS universe (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, genre VARCHAR(100) NOT NULL, short_description VARCHAR(500) NOT NULL, story_context LONGTEXT NOT NULL, themes JSON DEFAULT NULL, banner_image LONGBLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_universe_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE personnage ADD CONSTRAINT FK_6AEA486D5CD9AF2 FOREIGN KEY (universe_id) REFERENCES universe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponses ADD CONSTRAINT FK_1E512EC61E27F6BF FOREIGN KEY (question_id) REFERENCES questions (id)');
        // Skip problematic ALTER statements - these may have already been applied
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DF347EFB');
        $this->addSql('ALTER TABLE personnage DROP FOREIGN KEY FK_6AEA486D5CD9AF2');
        $this->addSql('ALTER TABLE reponses DROP FOREIGN KEY FK_1E512EC61E27F6BF');
        $this->addSql('DROP TABLE advanced_preferences');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE personnage');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE questions');
        $this->addSql('DROP TABLE reponses');
        $this->addSql('DROP TABLE universe');
        $this->addSql('ALTER TABLE artefacts DROP FOREIGN KEY FK_5E5C86F3B03A8386');
        $this->addSql('ALTER TABLE artefacts DROP FOREIGN KEY FK_5E5C86F3B03A8386');
        $this->addSql('ALTER TABLE artefacts ADD created_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_5e5c86f3b03a8386 ON artefacts');
        $this->addSql('CREATE INDEX FK_artefacts_created_by_id ON artefacts (created_by_id)');
        $this->addSql('ALTER TABLE artefacts ADD CONSTRAINT FK_5E5C86F3B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE defi DROP date_limite');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3EB03A8386');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3EB03A8386');
        $this->addSql('ALTER TABLE oeuvres ADD created_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_oeuvres_created_by_id FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('DROP INDEX idx_413eee3eb03a8386 ON oeuvres');
        $this->addSql('CREATE INDEX FK_oeuvres_created_by_id ON oeuvres (created_by_id)');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_413EEE3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `user` CHANGE is_verified is_verified TINYINT(1) NOT NULL');
    }
}
