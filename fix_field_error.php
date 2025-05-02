<?php
// Script to fix the specific InvalidFieldNameException issue

try {
    $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=localevent";
    $username = "postgres";
    $password = "SYS";
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to PostgreSQL database: localevent\n\n";
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get a list of all table columns - this helps detect which columns are missing
    $allColumns = [];
    $stmt = $pdo->query("SELECT table_name, column_name FROM information_schema.columns WHERE table_schema='public'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $allColumns[$row['table_name']][] = $row['column_name'];
    }
    
    echo "Checking for missing columns in key tables...\n\n";
    
    // CATEGORY TABLE FIXES
    echo "Category table fixes:\n";
    echo "---------------------\n";
    
    // Fix for category description (the most likely culprit based on the error)
    if (!in_array('description', $allColumns['category'] ?? [])) {
        echo "- Adding missing 'description' column to category table\n";
        $pdo->exec("ALTER TABLE category ADD COLUMN description TEXT NULL");
    } else {
        echo "- Category description column already exists\n";
    }
    
    // EVENT TABLE FIXES
    echo "\nEvent table fixes:\n";
    echo "-----------------\n";
    
    // Check for key fields in event table
    $eventRequiredFields = [
        'is_approved' => 'BOOLEAN DEFAULT false',
        'image' => 'TEXT NULL',
        'organizer_id' => 'INTEGER NULL',
        'updated_at' => 'TIMESTAMP WITHOUT TIME ZONE NULL'
    ];
    
    foreach ($eventRequiredFields as $field => $type) {
        if (!in_array($field, $allColumns['event'] ?? [])) {
            echo "- Adding missing '$field' column to event table\n";
            $pdo->exec("ALTER TABLE event ADD COLUMN $field $type");
        } else {
            echo "- Event $field column already exists\n";
        }
    }
    
    // USER TABLE FIXES
    echo "\nUser table fixes:\n";
    echo "----------------\n";
    
    // Check if app_user table exists and has required fields
    if (isset($allColumns['app_user'])) {
        $userRequiredFields = [
            'interests' => 'TEXT[] NULL'
        ];
        
        foreach ($userRequiredFields as $field => $type) {
            if (!in_array($field, $allColumns['app_user'])) {
                echo "- Adding missing '$field' column to app_user table\n";
                $pdo->exec("ALTER TABLE app_user ADD COLUMN $field $type");
            } else {
                echo "- User $field column already exists\n";
            }
        }
    } else {
        echo "- app_user table not found!\n";
    }
    
    // ATTENDANCE TABLE FIXES
    echo "\nAttendance table fixes:\n";
    echo "----------------------\n";
    
    // Check if attendance table exists
    if (isset($allColumns['attendance'])) {
        $attendanceRequiredFields = [
            'joined_at' => 'TIMESTAMP WITHOUT TIME ZONE NULL'
        ];
        
        foreach ($attendanceRequiredFields as $field => $type) {
            if (!in_array($field, $allColumns['attendance'])) {
                echo "- Adding missing '$field' column to attendance table\n";
                $pdo->exec("ALTER TABLE attendance ADD COLUMN $field $type");
            } else {
                echo "- Attendance $field column already exists\n";
            }
        }
    } else if (isset($allColumns['rsvp'])) {
        // Rename rsvp table to attendance if it exists
        echo "- Renaming rsvp table to attendance\n";
        $pdo->exec("ALTER TABLE rsvp RENAME TO attendance");
        
        // Check and add fields to the newly renamed table
        $attendanceRequiredFields = [
            'joined_at' => 'TIMESTAMP WITHOUT TIME ZONE NULL'
        ];
        
        foreach ($attendanceRequiredFields as $field => $type) {
            if (!in_array($field, $allColumns['rsvp'])) {
                echo "- Adding missing '$field' column to attendance table\n";
                $pdo->exec("ALTER TABLE attendance ADD COLUMN $field $type");
            }
        }
    } else {
        echo "- Neither attendance nor rsvp table found!\n";
    }
    
    // Commit changes
    $pdo->commit();
    echo "\n✅ All field fixes have been applied successfully!\n";
    
} catch (PDOException $e) {
    // Roll back on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
