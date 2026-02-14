<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260214132729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE advanced_preferences (id INT AUTO_INCREMENT NOT NULL, free_description LONGTEXT NOT NULL, favorite_genre VARCHAR(100) NOT NULL, affinity_level INT NOT NULL, favorite_themes LONGTEXT NOT NULL, custom_tags LONGTEXT NOT NULL, user_id VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE artefacts (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, universe VARCHAR(255) NOT NULL, origins LONGTEXT NOT NULL, powers LONGTEXT NOT NULL, rarity VARCHAR(50) NOT NULL, image_url VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_5E5C86F3B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, date_commande DATETIME NOT NULL, etat VARCHAR(50) NOT NULL, acheteur VARCHAR(255) NOT NULL, prix_total NUMERIC(10, 2) NOT NULL, reference_commande VARCHAR(100) NOT NULL, produit_id INT NOT NULL, UNIQUE INDEX UNIQ_6EEAA67DB6E0FD02 (reference_commande), INDEX IDX_6EEAA67DF347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE defi (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, theme VARCHAR(255) NOT NULL, image_cover VARCHAR(255) DEFAULT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, date_limite DATE DEFAULT NULL, statut VARCHAR(255) NOT NULL, createur_id INT NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE oeuvres (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, description LONGTEXT NOT NULL, date_publication DATE DEFAULT NULL, image_url VARCHAR(500) DEFAULT NULL, author VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_413EEE3EB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, date_soumission DATE NOT NULL, statut VARCHAR(255) NOT NULL, user_id INT NOT NULL, artwork_id INT DEFAULT NULL, image_file_name VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, defi_id INT NOT NULL, INDEX IDX_AB55E24F73F00F27 (defi_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE personnage (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, class_role VARCHAR(100) NOT NULL, history_context LONGTEXT NOT NULL, abilities_powers LONGTEXT DEFAULT NULL, strength INT DEFAULT NULL, agility INT DEFAULT NULL, magic INT DEFAULT NULL, defense INT DEFAULT NULL, portrait_image LONGBLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, universe_id INT NOT NULL, INDEX IDX_6AEA486D5CD9AF2 (universe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom_produit VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, prix NUMERIC(10, 2) NOT NULL, type_produit VARCHAR(100) NOT NULL, quantite_disponible INT NOT NULL, date_ajout DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE questions (id INT AUTO_INCREMENT NOT NULL, question VARCHAR(500) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE reponses (id INT AUTO_INCREMENT NOT NULL, `option` VARCHAR(500) NOT NULL, tag VARCHAR(100) NOT NULL, question_id INT NOT NULL, INDEX IDX_1E512EC61E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE universe (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, genre VARCHAR(100) NOT NULL, short_description VARCHAR(500) NOT NULL, story_context LONGTEXT NOT NULL, themes JSON DEFAULT NULL, banner_image LONGBLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_universe_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) DEFAULT NULL, role VARCHAR(20) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, is_blocked TINYINT(1) DEFAULT 0 NOT NULL, is_verified TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, phone_number VARCHAR(20) DEFAULT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_token_expires_at DATETIME DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, auth_provider VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D64976F5C865 (google_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE artefacts ADD CONSTRAINT FK_5E5C86F3B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_413EEE3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F73F00F27 FOREIGN KEY (defi_id) REFERENCES defi (id)');
        $this->addSql('ALTER TABLE personnage ADD CONSTRAINT FK_6AEA486D5CD9AF2 FOREIGN KEY (universe_id) REFERENCES universe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponses ADD CONSTRAINT FK_1E512EC61E27F6BF FOREIGN KEY (question_id) REFERENCES questions (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artefacts DROP FOREIGN KEY FK_5E5C86F3B03A8386');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DF347EFB');
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3EB03A8386');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F73F00F27');
        $this->addSql('ALTER TABLE personnage DROP FOREIGN KEY FK_6AEA486D5CD9AF2');
        $this->addSql('ALTER TABLE reponses DROP FOREIGN KEY FK_1E512EC61E27F6BF');
        $this->addSql('DROP TABLE advanced_preferences');
        $this->addSql('DROP TABLE artefacts');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE defi');
        $this->addSql('DROP TABLE oeuvres');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE personnage');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE questions');
        $this->addSql('DROP TABLE reponses');
        $this->addSql('DROP TABLE universe');
        $this->addSql('DROP TABLE `user`');
    }
}
