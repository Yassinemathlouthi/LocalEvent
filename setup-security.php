<?php

/**
 * LocalEvent Project Security Setup Script
 * This script helps users configure security best practices for the application.
 */

echo "==========================================================\n";
echo "LocalEvent Project - Security Setup\n";
echo "==========================================================\n\n";

// Check if running in production
$isProduction = getenv('APP_ENV') === 'prod';
if ($isProduction) {
    echo "⚠️ You're running in PRODUCTION environment.\n";
    echo "It's strongly recommended to set up Symfony Secrets for your credentials.\n\n";
    
    echo "📋 Follow these steps:\n";
    echo "1. Run: php bin/console secrets:set DATABASE_URL\n";
    echo "   (Enter your database URL when prompted)\n";
    echo "2. Make sure your .env.local file isn't tracked in Git\n";
    echo "3. Your application will automatically use these secrets in production\n\n";
} else {
    echo "🔧 You're running in DEVELOPMENT environment.\n";
    echo "Your credentials in .env.local will be used.\n\n";
}

// Database connection check
echo "Checking database connection...\n";
try {
    $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=localevent";
    $username = "postgres";
    $password = "SYS";
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful!\n\n";
    
    echo "📊 Database Information:\n";
    // Database version
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "   - PostgreSQL Version: " . $version . "\n";
    
    // Get list of tables
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   - Tables: " . implode(", ", $tables) . "\n\n";
    
    echo "🔒 Security Recommendations:\n";
    echo "1. Create a dedicated database user with limited permissions instead of using 'postgres'\n";
    echo "   - Run: CREATE USER localevent_user WITH PASSWORD 'secure_password';\n";
    echo "   - Run: GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO localevent_user;\n";
    echo "   - Update your DATABASE_URL accordingly\n";
    echo "2. Make sure HTTPS is enforced in production (already configured in security.yaml)\n";
    echo "3. Keep your APP_SECRET secure and different for each environment\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database credentials and make sure PostgreSQL is running.\n\n";
}

echo "==========================================================\n";
echo "Run 'php bin/console server:start' to start the application\n";
echo "==========================================================\n"; 