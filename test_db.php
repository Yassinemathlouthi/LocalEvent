<?php
echo "Testing PostgreSQL Connection...<br>";

try {
    $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=localevent";
    $username = "postgres";
    $password = "SYS";
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected successfully to PostgreSQL!<br>";
    
    // Test a query
    $stmt = $pdo->query("SELECT current_database()");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Connected to database: " . $result['current_database'] . "<br>";
    
    // Get list of tables
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
    echo "Database tables:<br>";
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>" . $row['table_name'] . "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "<br>";
}
?>
