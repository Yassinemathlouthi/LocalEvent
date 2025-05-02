<?php
// Final fixes for any remaining mismatches between Symfony entities and PostgreSQL

try {
    $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=localevent";
    $username = "postgres";
    $password = "SYS";
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to PostgreSQL database: localevent\n\n";
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // 1. Check for proper event -> category relationship
    echo "1. Fixing event to category relationship...\n";
    // Check if category_id column exists in event table
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='event' AND column_name='category_id'");
    if (!$stmt->fetch()) {
        echo "   Adding category_id column to event table...\n";
        $pdo->exec("ALTER TABLE event ADD COLUMN category_id INTEGER NULL REFERENCES category(id)");
    } else {
        echo "   ✅ category_id column already exists in event table\n";
    }
    
    // 2. Rename the 'created_by' to 'organizer_id' if needed
    echo "\n2. Checking event organizer field...\n";
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='event' AND column_name='created_by'");
    if ($stmt->fetch()) {
        $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='event' AND column_name='organizer_id'");
        if ($stmt->fetch()) {
            echo "   Both created_by and organizer_id exist - making sure they're in sync...\n";
            $pdo->exec("UPDATE event SET organizer_id = created_by WHERE organizer_id IS NULL");
        } else {
            echo "   Renaming created_by to organizer_id...\n";
            $pdo->exec("ALTER TABLE event RENAME COLUMN created_by TO organizer_id");
        }
    } else {
        echo "   ✅ No rename needed for organizer field\n";
    }
    
    // 3. Make sure date field is properly handled
    echo "\n3. Checking event date fields...\n";
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='event' AND column_name='date'");
    $hasDate = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='event' AND column_name='time'");
    $hasTime = $stmt->fetch();
    
    if ($hasDate && $hasTime) {
        echo "   Combining separate date and time fields...\n";
        // First check if we already have the event_date timestamp field
        $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='event' AND column_name='event_date'");
        if (!$stmt->fetch()) {
            // Add a combined timestamp field
            $pdo->exec("ALTER TABLE event ADD COLUMN event_date TIMESTAMP WITHOUT TIME ZONE");
            // Populate it with the combined values
            $pdo->exec("UPDATE event SET event_date = (date::text || ' ' || time::text)::timestamp");
        } else {
            echo "   ✅ event_date already exists, ensuring it contains the combined values...\n";
            $pdo->exec("UPDATE event SET event_date = (date::text || ' ' || time::text)::timestamp WHERE event_date IS NULL");
        }
    } else {
        echo "   ✅ No need to combine date/time fields\n";
    }
    
    // 4. Additional essential fixes for possible mismatches
    
    // 4.1 User table - ensuring proper column names
    echo "\n4. Checking user table...\n";
    
    // Check if we need to rename app_user to user
    $stmt = $pdo->query("SELECT * FROM information_schema.tables WHERE table_name='app_user'");
    $hasAppUser = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT * FROM information_schema.tables WHERE table_name='\"user\"'");
    $hasUserTable = $stmt->fetch();
    
    if ($hasAppUser && !$hasUserTable) {
        echo "   ℹ️ app_user table exists, but 'user' doesn't.\n";
        echo "      This might be normal - check that your Entity uses @ORM\\Table(name=\"app_user\")\n";
    }
    
    // 5. Rebuild materialized views if they exist
    echo "\n5. Refreshing materialized views (if any)...\n";
    try {
        $stmt = $pdo->query("SELECT matviewname FROM pg_matviews WHERE schemaname='public'");
        $viewCount = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $viewName = $row['matviewname'];
            echo "   Refreshing materialized view: $viewName\n";
            $pdo->exec("REFRESH MATERIALIZED VIEW $viewName");
            $viewCount++;
        }
        if ($viewCount == 0) {
            echo "   ✅ No materialized views found\n";
        }
    } catch (PDOException $e) {
        echo "   ⚠️ " . $e->getMessage() . "\n";
    }
    
    // Commit changes
    $pdo->commit();
    echo "\n✅ All final fixes have been applied successfully!\n";
    echo "\nNext Steps: Please clear Symfony cache and restart the server:\n";
    echo "1. php bin/console cache:clear\n";
    echo "2. symfony server:restart\n";
    
} catch (PDOException $e) {
    // Roll back on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
