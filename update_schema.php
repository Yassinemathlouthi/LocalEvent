<?php
// Schema update script to match Symfony entities

try {
    $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=localevent";
    $username = "postgres";
    $password = "SYS";
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to PostgreSQL database: localevent\n\n";
    
    // Begin transaction for safety
    $pdo->beginTransaction();
    
    // 1. Add description column to category table
    echo "1. Adding 'description' column to 'category' table...\n";
    try {
        $pdo->exec("ALTER TABLE category ADD COLUMN description TEXT NULL");
        echo "   ✅ Added successfully\n";
    } catch (PDOException $e) {
        echo "   ⚠️ " . $e->getMessage() . "\n";
    }
    
    // 2. Fix event table category relationship
    echo "\n2. Converting event table category field to proper relation...\n";
    
    // First create a backup of the category data
    try {
        $pdo->exec("ALTER TABLE event RENAME COLUMN category TO category_name");
        echo "   ✅ Renamed category column to category_name for data preservation\n";
    } catch (PDOException $e) {
        echo "   ⚠️ " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE event ADD COLUMN category_id INTEGER NULL REFERENCES category(id)");
        echo "   ✅ Added category_id column with proper foreign key\n";
    } catch (PDOException $e) {
        echo "   ⚠️ " . $e->getMessage() . "\n";
    }
    
    // 3. Add any missing columns to match Symfony entities
    // For User entity
    echo "\n3. Adding missing columns to tables if needed...\n";
    // Check if interests column needs to be added to app_user
    try {
        $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='app_user' AND column_name='interests'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE app_user ADD COLUMN interests TEXT[] NULL");
            echo "   ✅ Added interests column to app_user table\n";
        }
    } catch (PDOException $e) {
        echo "   ⚠️ " . $e->getMessage() . "\n";
    }
    
    // Add is_approved to event table
    try {
        $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='event' AND column_name='is_approved'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE event ADD COLUMN is_approved BOOLEAN DEFAULT false");
            echo "   ✅ Added is_approved column to event table\n";
        }
    } catch (PDOException $e) {
        echo "   ⚠️ " . $e->getMessage() . "\n";
    }
    
    // Add image to event table
    try {
        $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='event' AND column_name='image'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE event ADD COLUMN image TEXT NULL");
            echo "   ✅ Added image column to event table\n";
        }
    } catch (PDOException $e) {
        echo "   ⚠️ " . $e->getMessage() . "\n";
    }
    
    // 4. Rename rsvp table to attendance if needed
    echo "\n4. Checking if we need to rename rsvp table to attendance...\n";
    try {
        $stmt = $pdo->query("SELECT * FROM information_schema.tables WHERE table_name='attendance'");
        if (!$stmt->fetch()) {
            // First check if rsvp table exists
            $stmt = $pdo->query("SELECT * FROM information_schema.tables WHERE table_name='rsvp'");
            if ($stmt->fetch()) {
                $pdo->exec("ALTER TABLE rsvp RENAME TO attendance");
                echo "   ✅ Renamed rsvp table to attendance\n";
            }
        } else {
            echo "   ✅ attendance table already exists\n";
        }
    } catch (PDOException $e) {
        echo "   ⚠️ " . $e->getMessage() . "\n";
    }
    
    // Commit all changes
    $pdo->commit();
    echo "\n✅ All schema updates completed successfully!\n";
    echo "\nNow restart your Symfony application and it should work with the updated schema.\n";
    
} catch (PDOException $e) {
    // Roll back transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
