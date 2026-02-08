<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207170230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Defi and Participation tables with initial schema';
    }

    public function up(Schema $schema): void
    {
        // Create defi table
        $defi = $schema->createTable('defi');
        $defi->addColumn('id', 'integer', ['autoincrement' => true]);
        $defi->addColumn('titre', 'string', ['length' => 255]);
        $defi->addColumn('description', 'string', ['length' => 255]);
        $defi->addColumn('thème', 'string', ['length' => 255]);
        $defi->addColumn('image_cover', 'string', ['length' => 299, 'notnull' => false]);
        $defi->addColumn('date_debut', 'date');
        $defi->addColumn('date_fin', 'date');
        $defi->addColumn('statut', 'string', ['length' => 255]);
        $defi->addColumn('createur_id', 'integer');
        $defi->setPrimaryKey(['id']);
        
        // Create participation table
        $participation = $schema->createTable('participation');
        $participation->addColumn('id', 'integer', ['autoincrement' => true]);
        $participation->addColumn('description', 'string', ['length' => 255]);
        $participation->addColumn('date_soumission', 'date');
        $participation->addColumn('statut', 'string', ['length' => 255, 'notnull' => false]);
        $participation->addColumn('user_id', 'integer');
        $participation->addColumn('artwork_id', 'integer');
        $participation->addColumn('defi_id', 'integer');
        $participation->setPrimaryKey(['id']);
        $participation->addForeignKeyConstraint('defi', ['defi_id'], ['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('participation');
        $schema->dropTable('defi');
    }
}
