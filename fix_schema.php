<?php
// Script to diagnose and fix schema issues between Symfony entities and PostgreSQL database

echo "===== LocalEvent Database Schema Diagnostic Tool =====\n\n";

// 1. Test basic connection
echo "1. Testing PostgreSQL connection...\n";
try {
    $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=localevent";
    $username = "postgres";
    $password = "SYS";
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected successfully to PostgreSQL!\n";
    
    // Get database info
    $stmt = $pdo->query("SELECT current_database()");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Database: " . $result['current_database'] . "\n";
    
    // 2. Get list of tables
    echo "\n2. Listing database tables:\n";
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
    $tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tables[] = $row['table_name'];
        echo "   - " . $row['table_name'] . "\n";
    }
    
    // 3. Check specific tables for structure
    echo "\n3. Analyzing key tables...\n";
    
    // Check if category table exists and examine its structure
    if (in_array('category', $tables)) {
        echo "\n   Category table structure:\n";
        $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='category'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - " . $row['column_name'] . " (" . $row['data_type'] . ")\n";
        }
    } else {
        echo "   ❌ Category table not found!\n";
    }
    
    // Check if event table exists and examine its structure
    if (in_array('event', $tables)) {
        echo "\n   Event table structure:\n";
        $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='event'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - " . $row['column_name'] . " (" . $row['data_type'] . ")\n";
        }
    } else {
        echo "   ❌ Event table not found!\n";
    }
    
    // Check if user table exists
    if (in_array('user', $tables) || in_array('"user"', $tables)) {
        $userTable = in_array('user', $tables) ? 'user' : '"user"';
        echo "\n   User table structure:\n";
        $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='$userTable'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - " . $row['column_name'] . " (" . $row['data_type'] . ")\n";
        }
    } else {
        echo "   ❌ User table not found!\n";
    }
    
    echo "\n=== Next Steps ===\n";
    echo "1. Check if the columns listed above match your Symfony entity definitions\n";
    echo "2. If columns are missing, you can:\n";
    echo "   a. Modify your database tables to add missing columns, or\n";
    echo "   b. Update your Symfony entities to match existing database structure\n";
    echo "3. For best results, ensure your database tables include all columns defined in your entities\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
?>
