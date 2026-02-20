<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209040800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dateLimite field to Defi entity';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Check if column already exists to avoid duplicate column error
        if ($schema->hasTable('defi')) {
            $table = $schema->getTable('defi');
            if (!$table->hasColumn('date_limite')) {
                $this->addSql('ALTER TABLE defi ADD date_limite DATE DEFAULT NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE defi DROP date_limite');
    }
}
