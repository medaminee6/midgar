<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create stock_prediction table for AI-based stock forecasting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stock_prediction (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, predicted_stockout_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', days_until_stockout INT NOT NULL, daily_consumption DOUBLE PRECISION NOT NULL, current_stock INT NOT NULL, confidence DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', metadata JSON DEFAULT NULL, INDEX IDX_STOCK_PRED_PRODUCT (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stock_prediction ADD CONSTRAINT FK_STOCK_PRED_PRODUCT FOREIGN KEY (product_id) REFERENCES produit (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_prediction DROP FOREIGN KEY FK_STOCK_PRED_PRODUCT');
        $this->addSql('DROP TABLE stock_prediction');
    }
}
