<?php

/**
 * LocalEvent Project - Database Schema Fix
 * This script updates the database schema to match the Symfony entity definitions
 */

echo "==========================================================\n";
echo "🔧 LocalEvent Project - Database Schema Fix\n";
echo "==========================================================\n\n";

// Database connection parameters
$host = '127.0.0.1';
$port = '5432';
$user = 'postgres';
$password = 'SYS';
$dbname = 'localevent';

try {
    echo "Connecting to database...\n";
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get list of tables
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Current tables: " . implode(", ", $tables) . "\n\n";
    
    echo "1. Fixing table name issues...\n";
    // Rename rsvp table to attendance if it exists and attendance doesn't
    if (in_array('rsvp', $tables) && !in_array('attendance', $tables)) {
        echo "   - Renaming 'rsvp' table to 'attendance'...\n";
        $pdo->exec("ALTER TABLE rsvp RENAME TO attendance");
        $pdo->exec("ALTER TABLE attendance RENAME COLUMN status TO status");
        $pdo->exec("ALTER TABLE attendance RENAME COLUMN responded_at TO created_at");
    }
    
    echo "2. Fixing roles column in app_user table...\n";
    // Check the current data type of the roles column
    $stmt = $pdo->query("SELECT data_type FROM information_schema.columns WHERE table_name = 'app_user' AND column_name = 'roles'");
    $dataType = $stmt->fetchColumn();
    
    echo "   - Current data type of roles column: " . $dataType . "\n";
    
    if ($dataType == '_text') {
        echo "   - Converting TEXT[] roles to JSON...\n";
        
        // Create a backup of the app_user table first
        $pdo->exec("CREATE TABLE app_user_backup AS SELECT * FROM app_user");
        echo "   - Created backup table 'app_user_backup'\n";
        
        // Get all users
        $users = $pdo->query("SELECT id, roles FROM app_user")->fetchAll(PDO::FETCH_ASSOC);
        
        // Add a new JSON column
        $pdo->exec("ALTER TABLE app_user ADD COLUMN roles_json JSON NULL");
        
        // Convert each user's roles array to JSON
        foreach ($users as $user) {
            $id = $user['id'];
            $roles = $user['roles'];
            
            // Convert PostgreSQL array to JSON
            if ($roles !== null) {
                // Handle text[] array format like {ROLE_USER,ROLE_ADMIN}
                $roles = str_replace(['{', '}'], ['[', ']'], $roles);
                // Replace quotes if needed and ensure proper JSON format
                $roles = preg_replace('/([a-zA-Z0-9_]+)/', '"$1"', $roles);
                $roles = str_replace('"ROLE_USER"', '"ROLE_USER"', $roles);
                $roles = str_replace('"ROLE_ADMIN"', '"ROLE_ADMIN"', $roles);
            } else {
                $roles = '["ROLE_USER"]';
            }
            
            $stmt = $pdo->prepare("UPDATE app_user SET roles_json = :roles::json WHERE id = :id");
            $stmt->execute(['roles' => $roles, 'id' => $id]);
        }
        
        // Drop the old column and rename the new one
        $pdo->exec("ALTER TABLE app_user DROP COLUMN roles");
        $pdo->exec("ALTER TABLE app_user RENAME COLUMN roles_json TO roles");
        $pdo->exec("ALTER TABLE app_user ALTER COLUMN roles SET NOT NULL");
        
        echo "   - Roles column successfully converted to JSON\n";
    }
    
    echo "3. Updating Symfony sequence names...\n";
    
    // Check if sequences need to be created or renamed
    $sequences = $pdo->query("SELECT sequencename FROM pg_sequences WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
    
    // Create necessary sequences if they don't exist
    $requiredSequences = [
        'app_user_id_seq' => 'app_user',
        'event_id_seq' => 'event',
        'category_id_seq' => 'category'
    ];
    
    foreach ($requiredSequences as $sequence => $table) {
        if (!in_array($sequence, $sequences)) {
            echo "   - Creating sequence '$sequence' for table '$table'...\n";
            $pdo->exec("CREATE SEQUENCE IF NOT EXISTS $sequence");
            $pdo->exec("SELECT setval('$sequence', (SELECT COALESCE(MAX(id), 0) FROM $table))");
        }
    }
    
    echo "4. Adding missing columns required by Symfony entities...\n";
    
    // Check if event table has time column
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'event' AND column_name = 'time'");
    if (!$stmt->fetch()) {
        echo "   - Adding 'time' column to event table...\n";
        $pdo->exec("ALTER TABLE event ADD COLUMN IF NOT EXISTS time TIME NOT NULL DEFAULT CURRENT_TIME");
    }
    
    // Check if event table has is_approved column
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'event' AND column_name = 'is_approved'");
    if (!$stmt->fetch()) {
        echo "   - Adding 'is_approved' column to event table...\n";
        $pdo->exec("ALTER TABLE event ADD COLUMN IF NOT EXISTS is_approved BOOLEAN DEFAULT FALSE");
    }
    
    // Rename the created_by column to organizer_id if needed
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'event' AND column_name = 'created_by'");
    if ($stmt->fetch()) {
        echo "   - Renaming 'created_by' column to 'organizer_id' in event table...\n";
        $pdo->exec("ALTER TABLE event RENAME COLUMN created_by TO organizer_id");
    }
    
    echo "5. Creating messenger_messages table for Symfony...\n";
    
    // Create messenger_messages table if it doesn't exist
    if (!in_array('messenger_messages', $tables)) {
        $pdo->exec("
            CREATE TABLE messenger_messages (
                id BIGSERIAL NOT NULL, 
                body TEXT NOT NULL, 
                headers TEXT NOT NULL, 
                queue_name VARCHAR(190) NOT NULL, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        
        // Add indexes and comments for Messenger Messages
        $pdo->exec("CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)");
        $pdo->exec("CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)");
        $pdo->exec("CREATE INDEX IDX_75EA56E0016BA31DB ON messenger_messages (delivered_at)");
        $pdo->exec("COMMENT ON COLUMN messenger_messages.created_at IS '(DC2Type:datetime_immutable)'");
        $pdo->exec("COMMENT ON COLUMN messenger_messages.available_at IS '(DC2Type:datetime_immutable)'");
        $pdo->exec("COMMENT ON COLUMN messenger_messages.delivered_at IS '(DC2Type:datetime_immutable)'");
        
        // Create notification function and trigger for Messenger
        $pdo->exec("
            CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
                BEGIN
                    PERFORM pg_notify('messenger_messages', NEW.queue_name::text);
                    RETURN NEW;
                END;
            $$ LANGUAGE plpgsql
        ");
        $pdo->exec("DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages");
        $pdo->exec("CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages()");
    }
    
    echo "\nSchema update complete! Now clearing Symfony cache...\n";
    system('php bin/console cache:clear');
    
    echo "\nValidating updated schema...\n";
    system('php bin/console doctrine:schema:validate');
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n==========================================================\n";
echo "Next steps:\n";
echo "1. If schema validation still shows errors, run: php bin/console doctrine:schema:update --force\n";
echo "2. Start the server: php -S localhost:8000 -t public/\n";
echo "==========================================================\n"; 