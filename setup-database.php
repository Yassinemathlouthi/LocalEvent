<?php

/**
 * LocalEvent Project Database Setup Script
 * This script ensures the database exists and is properly configured.
 */

echo "==========================================================\n";
echo "LocalEvent Project - Database Setup\n";
echo "==========================================================\n\n";

// Database connection parameters
$host = '127.0.0.1';
$port = '5432';
$user = 'postgres';
$password = 'SYS';
$dbname = 'localevent';

// First check PostgreSQL connection without database
echo "Checking PostgreSQL server connection... ";
try {
    $pdo = new PDO("pgsql:host=$host;port=$port", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to PostgreSQL server\n";
    
    // Check if database exists
    echo "Checking if database '$dbname' exists... ";
    $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '$dbname'");
    if ($stmt->fetchColumn()) {
        echo "✅ Database exists\n";
    } else {
        echo "❌ Database does not exist\n";
        echo "Creating database '$dbname'... ";
        $pdo->exec("CREATE DATABASE $dbname");
        echo "✅ Database created successfully\n";
    }
    
    // Connect to the target database
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get list of tables
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "✅ Database has " . count($tables) . " tables: " . implode(", ", $tables) . "\n";
    } else {
        echo "⚠️ Database has no tables. You should run migrations.\n";
        echo "   Run: php bin/console doctrine:migrations:migrate\n";
    }
    
    // Show row counts for major tables if they exist
    $majorTables = ['user', 'event', 'category', 'attendance'];
    foreach ($majorTables as $table) {
        if (in_array($table, $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM \"$table\"");
            $count = $stmt->fetchColumn();
            echo "   - $table: $count rows\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database credentials and make sure PostgreSQL is running.\n";
    exit(1);
}

// Run Symfony commands to check and verify database setup
echo "\nRunning Symfony database commands...\n";

// Check schema validity
echo "Checking schema validity...\n";
$output = [];
exec('php bin/console doctrine:schema:validate', $output, $returnCode);
echo implode("\n", $output) . "\n";

echo "\n==========================================================\n";
echo "Next steps:\n";
echo "1. If tables don't exist, run: php bin/console doctrine:migrations:migrate\n";
echo "2. To start the application: php -S localhost:8000 -t public/\n";
echo "==========================================================\n"; 