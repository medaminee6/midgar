<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shop automation event log table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS shop_automation_event (
            id INT AUTO_INCREMENT NOT NULL,
            product_id INT DEFAULT NULL,
            type VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL,
            payload JSON DEFAULT NULL,
            created_at DATETIME NOT NULL,
            processed_at DATETIME DEFAULT NULL,
            INDEX IDX_SHOP_AUTOMATION_EVENT_PRODUCT (product_id),
            INDEX IDX_SHOP_AUTOMATION_EVENT_TYPE (type),
            INDEX IDX_SHOP_AUTOMATION_EVENT_CREATED (created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE shop_automation_event ADD CONSTRAINT FK_SHOP_AUTOMATION_EVENT_PRODUCT FOREIGN KEY (product_id) REFERENCES produit (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shop_automation_event DROP FOREIGN KEY FK_SHOP_AUTOMATION_EVENT_PRODUCT');
        $this->addSql('DROP TABLE IF EXISTS shop_automation_event');
    }
}
