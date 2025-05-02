<?php

echo "Fixing roles column in app_user table...\n";

try {
    $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=localevent";
    $username = "postgres";
    $password = "SYS";
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check the current data type of the roles column
    $stmt = $pdo->query("SELECT data_type FROM information_schema.columns WHERE table_name = 'app_user' AND column_name = 'roles'");
    $dataType = $stmt->fetchColumn();
    
    echo "Current data type of roles column: " . $dataType . "\n";
    
    // Check if there are any rows in the app_user table
    $stmt = $pdo->query("SELECT COUNT(*) FROM app_user");
    $count = $stmt->fetchColumn();
    
    echo "Number of rows in app_user table: " . $count . "\n";
    
    if ($count == 0) {
        // If there are no users, we can just modify the column directly
        echo "No users found. Recreating the roles column...\n";
        
        // Drop the column and recreate it
        $pdo->exec("ALTER TABLE app_user DROP COLUMN roles");
        $pdo->exec("ALTER TABLE app_user ADD COLUMN roles JSON NOT NULL DEFAULT '[]'::json");
        
        echo "Roles column successfully recreated as JSON type.\n";
    } else {
        // There are users, so we need to preserve the data
        echo "Users found. Creating temporary column...\n";
        
        // Create a temporary JSON column
        $pdo->exec("ALTER TABLE app_user ADD COLUMN roles_json JSON NULL");
        
        // Copy data from roles to roles_json, handling text[] to JSON conversion
        $rows = $pdo->query("SELECT id, roles FROM app_user")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $id = $row['id'];
            $roles = $row['roles'];
            
            // Convert the PostgreSQL array to PHP array
            if ($dataType == '_text') {
                // Handle text[] array format
                $roles = str_replace(['{', '}'], ['[', ']'], $roles);
                $rolesJson = json_encode(json_decode($roles, true) ?: ['ROLE_USER']);
            } else {
                // Already JSON or can't determine, try direct conversion
                $rolesJson = json_encode(['ROLE_USER']);
            }
            
            // Update the temporary column
            $stmt = $pdo->prepare("UPDATE app_user SET roles_json = :roles WHERE id = :id");
            $stmt->execute(['roles' => $rolesJson, 'id' => $id]);
        }
        
        // Drop the old column and rename the new one
        $pdo->exec("ALTER TABLE app_user DROP COLUMN roles");
        $pdo->exec("ALTER TABLE app_user RENAME COLUMN roles_json TO roles");
        $pdo->exec("ALTER TABLE app_user ALTER COLUMN roles SET NOT NULL");
        
        echo "Roles column successfully converted to JSON type, preserving data.\n";
    }
    
    // Final check
    $stmt = $pdo->query("SELECT data_type FROM information_schema.columns WHERE table_name = 'app_user' AND column_name = 'roles'");
    $newDataType = $stmt->fetchColumn();
    
    echo "New data type of roles column: " . $newDataType . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Done fixing database. Please now clear the cache with 'php bin/console cache:clear'.\n"; 