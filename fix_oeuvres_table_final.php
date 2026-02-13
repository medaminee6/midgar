<?php

require __DIR__.'/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$connectionParams = [
    'dbname' => 'midgar1',
    'user' => 'root',
    'password' => '',
    'host' => '127.0.0.1',
    'driver' => 'pdo_mysql',
];

try {
    $conn = DriverManager::getConnection($connectionParams);
    
    // Drop and recreate the oeuvres table with correct schema
    $conn->executeStatement("DROP TABLE IF EXISTS oeuvres");
    
    $sql = "CREATE TABLE IF NOT EXISTS oeuvres (
        id INT AUTO_INCREMENT NOT NULL,
        title VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        description LONGTEXT NOT NULL,
        date_publication DATE DEFAULT NULL,
        image_url VARCHAR(500) DEFAULT NULL,
        author VARCHAR(255) DEFAULT NULL,
        created_by_id INT DEFAULT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX IDX_OEUVRES_CREATED_BY (created_by_id),
        PRIMARY KEY(id),
        CONSTRAINT FK_OEUVRES_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB";
    
    $conn->executeStatement($sql);
    echo "Table 'oeuvres' recreated with correct schema.\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}