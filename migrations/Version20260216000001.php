<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_preferences table to store quiz tag selections per user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE user_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL UNIQUE,
                tags LONGTEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS user_preferences');
    }
}
