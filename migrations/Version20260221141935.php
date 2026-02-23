<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221141935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE oeuvres ADD universe_id INT NOT NULL');
        $this->addSql('ALTER TABLE oeuvres ADD CONSTRAINT FK_413EEE3E5CD9AF2 FOREIGN KEY (universe_id) REFERENCES universe (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_413EEE3E5CD9AF2 ON oeuvres (universe_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE oeuvres DROP FOREIGN KEY FK_413EEE3E5CD9AF2');
        $this->addSql('DROP INDEX IDX_413EEE3E5CD9AF2 ON oeuvres');
        $this->addSql('ALTER TABLE oeuvres DROP universe_id');
    }
}
