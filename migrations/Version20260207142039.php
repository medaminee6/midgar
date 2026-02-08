<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207142039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Produit and Commande tables';
    }

    public function up(Schema $schema): void
    {
        // Create produit table
        $this->addSql('CREATE TABLE IF NOT EXISTS produit (
            id INT AUTO_INCREMENT NOT NULL,
            nom_produit VARCHAR(255) NOT NULL,
            description LONGTEXT,
            prix DECIMAL(10, 2) NOT NULL,
            type_produit VARCHAR(100) NOT NULL,
            quantite_disponible INT NOT NULL,
            date_ajout DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create commande table with ENUM for etat
        $this->addSql('CREATE TABLE IF NOT EXISTS commande (
            id INT AUTO_INCREMENT NOT NULL,
            produit_id INT NOT NULL,
            quantite INT NOT NULL,
            date_commande DATETIME NOT NULL,
            etat VARCHAR(50) NOT NULL DEFAULT "en_attente",
            acheteur VARCHAR(255) NOT NULL,
            prix_total DECIMAL(10, 2) NOT NULL,
            reference_commande VARCHAR(100) NOT NULL UNIQUE,
            PRIMARY KEY (id),
            FOREIGN KEY (produit_id) REFERENCES produit (id),
            INDEX idx_produit_id (produit_id),
            INDEX idx_reference (reference_commande)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS commande');
        $this->addSql('DROP TABLE IF EXISTS produit');
    }
}
