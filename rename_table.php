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
    
    // Rename oeuvre to oeuvres
    $conn->executeStatement("RENAME TABLE oeuvre TO oeuvres");
    echo "Table 'oeuvre' renamed to 'oeuvres' successfully.\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}