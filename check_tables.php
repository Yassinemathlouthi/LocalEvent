<?php
// Detailed database structure check

try {
    $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=localevent";
    $username = "postgres";
    $password = "SYS";
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to PostgreSQL database: localevent\n\n";
    
    // List all tables
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name");
    $tables = [];
    echo "DATABASE TABLES:\n";
    echo "===============\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tables[] = $row['table_name'];
        echo "- " . $row['table_name'] . "\n";
    }
    
    echo "\n\nDETAILED TABLE STRUCTURES:\n";
    echo "========================\n";
    
    // Get structure for each table
    foreach ($tables as $table) {
        echo "\nTABLE: " . $table . "\n";
        echo "-------------------------\n";
        $stmt = $pdo->query("SELECT column_name, data_type, character_maximum_length, is_nullable 
                            FROM information_schema.columns 
                            WHERE table_name='$table' 
                            ORDER BY ordinal_position");
        
        echo sprintf("%-25s %-15s %-8s %-8s\n", "COLUMN", "TYPE", "LENGTH", "NULLABLE");
        echo str_repeat("-", 60) . "\n";
        
        while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $type = $col['data_type'];
            $length = $col['character_maximum_length'] ? $col['character_maximum_length'] : 'N/A';
            $nullable = $col['is_nullable'] == 'YES' ? 'YES' : 'NO';
            
            echo sprintf("%-25s %-15s %-8s %-8s\n", 
                $col['column_name'], 
                $type,
                $length,
                $nullable
            );
        }
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>
