<?php

/**
 * LocalEvent Project Database Rebuild Script
 * This script completely rebuilds the database schema from scratch
 */

echo "==========================================================\n";
echo "🔄 LocalEvent Project - Database Rebuild\n";
echo "==========================================================\n\n";

// Database connection parameters
$host = '127.0.0.1';
$port = '5432';
$user = 'postgres';
$password = 'SYS';
$dbname = 'localevent';

try {
    // Connect to PostgreSQL server
    echo "Connecting to PostgreSQL server...\n";
    $pdo = new PDO("pgsql:host=$host;port=$port", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop database if it exists
    echo "Checking if database '$dbname' exists...\n";
    $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '$dbname'");
    if ($stmt->fetchColumn()) {
        echo "Database exists. Dropping it...\n";
        
        // Close all connections to the database first
        $pdo->exec("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$dbname'");
        $pdo->exec("DROP DATABASE $dbname");
        echo "Database dropped successfully.\n";
    }
    
    // Create fresh database
    echo "Creating new database '$dbname'...\n";
    $pdo->exec("CREATE DATABASE $dbname");
    echo "Database created successfully.\n";
    
    // Connect to the new database
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables directly with SQL
    echo "Creating tables...\n";
    
    // Create Messenger Messages table (for Symfony messaging)
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
    $pdo->exec("CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)");
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
    $pdo->exec("CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages()");
    
    // User table
    $pdo->exec("
        CREATE TABLE app_user (
            id INT NOT NULL,
            email VARCHAR(180) NOT NULL UNIQUE,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            location VARCHAR(255) NULL,
            interests TEXT[] NULL,
            PRIMARY KEY(id)
        )
    ");
    
    // Create sequence for user id
    $pdo->exec("CREATE SEQUENCE app_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
    $pdo->exec("ALTER TABLE app_user ALTER id SET DEFAULT nextval('app_user_id_seq')");
    $pdo->exec("ALTER INDEX app_user_email_key RENAME TO UNIQ_88BDF3E9E7927C74");
    
    // Category table
    $pdo->exec("
        CREATE TABLE category (
            id INT NOT NULL,
            name VARCHAR(50) NOT NULL,
            PRIMARY KEY(id)
        )
    ");
    
    // Create sequence for category id
    $pdo->exec("CREATE SEQUENCE category_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
    $pdo->exec("ALTER TABLE category ALTER id SET DEFAULT nextval('category_id_seq')");
    
    // Event table
    $pdo->exec("
        CREATE TABLE event (
            id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            location VARCHAR(255) NULL,
            date TIMESTAMP NULL,
            time TIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            category VARCHAR(255) NULL,
            is_approved BOOLEAN DEFAULT FALSE,
            PRIMARY KEY(id)
        )
    ");
    
    // Create sequence for event id
    $pdo->exec("CREATE SEQUENCE event_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
    $pdo->exec("ALTER TABLE event ALTER id SET DEFAULT nextval('event_id_seq')");
    
    echo "Schema created successfully.\n";
    
    // Add test data
    echo "Adding test data...\n";
    
    // Add admin user
    $password = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("
        INSERT INTO app_user (email, roles, password, name, location) 
        VALUES ('admin@example.com', '[\"ROLE_ADMIN\", \"ROLE_USER\"]'::json, '$password', 'Admin User', 'New York')
    ");
    
    // Add categories
    $categories = ['Conference', 'Workshop', 'Networking', 'Party', 'Other'];
    foreach ($categories as $category) {
        $pdo->exec("INSERT INTO category (name) VALUES ('$category')");
    }
    
    echo "Test data added successfully.\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n==========================================================\n";
echo "✅ Database rebuilt successfully!\n";
echo "==========================================================\n";
echo "Next steps:\n";
echo "1. Clear the cache: php bin/console cache:clear\n";
echo "2. Validate schema: php bin/console doctrine:schema:validate\n";
echo "3. Start the server: php -S localhost:8000 -t public/\n";
echo "==========================================================\n";

// Execute the next steps automatically
echo "\nExecuting next steps automatically...\n";

// Clear cache
echo "Clearing cache...\n";
system('php bin/console cache:clear');

// Validate schema
echo "\nValidating schema...\n";
system('php bin/console doctrine:schema:validate'); 