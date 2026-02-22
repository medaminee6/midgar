<?php
/**
 * Create database named "midgar 2" and run migrations found in /migrations
 *
 * Usage: php create_and_migrate_database.php
 */

$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$password = '';
$database = 'midgar_2';
$migrationsDir = __DIR__ . DIRECTORY_SEPARATOR . 'migrations';

try {
    // Connect without database to create it
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create database with proper charset/collation
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`','\\`',$database) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$database' created (or already exists).\n";

    // Connect to the newly created database
    $dsn = "mysql:host=$host;port=$port;dbname=" . $database;
    $pdoDb = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    if (!is_dir($migrationsDir)) {
        echo "Migrations directory not found: $migrationsDir\n";
        exit(1);
    }

    $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.php');
    sort($files, SORT_STRING);
    if (empty($files)) {
        echo "No migration files found in $migrationsDir\n";
        exit(0);
    }

    foreach ($files as $file) {
        echo "Processing migration: " . basename($file) . "\n";
        $content = file_get_contents($file);

        // Find all $this->addSql('...'); occurrences and extract the SQL
        $pattern = '/\$this->addSql\(\s*(["\'])(.*?)(?<!\\)\1\s*\);/s';
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[2] as $sql) {
                $sql = trim($sql);
                if ($sql === '') continue;
                echo "  Executing: " . substr($sql, 0, 120) . (strlen($sql) > 120 ? '...' : '') . "\n";
                try {
                    $pdoDb->exec($sql);
                } catch (PDOException $e) {
                    echo "    ERROR executing SQL: " . $e->getMessage() . "\n";
                    // Continue with next SQL/migration
                }
            }
        } else {
            echo "  No addSql() statements found in " . basename($file) . "\n";
        }
    }

    echo "Migrations processing completed.\n";

} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
    exit(1);
}
