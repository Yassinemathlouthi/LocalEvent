<?php

/**
 * LocalEvent Project Setup Script
 * This is the main setup script that runs all required setup steps
 */

echo "==========================================================\n";
echo "🎉 LocalEvent Project - Complete Setup\n";
echo "==========================================================\n\n";

echo "Step 1: Setting up the database\n";
echo "==========================================================\n";
include_once 'setup-database.php';

echo "\nStep 2: Setting up security configuration\n";
echo "==========================================================\n";
include_once 'setup-security.php';

// Check if other required tools are installed
echo "\nChecking Composer dependencies...\n";
if (!file_exists('vendor/autoload.php')) {
    echo "❌ Composer dependencies not installed.\n";
    echo "   Run: composer install\n";
} else {
    echo "✅ Composer dependencies installed.\n";
}

// Check Node.js dependencies
echo "\nChecking Node.js dependencies...\n";
if (!file_exists('node_modules')) {
    echo "❌ Node.js dependencies not installed.\n";
    echo "   Run: npm install\n";
} else {
    echo "✅ Node.js dependencies installed.\n";
}

// Check Symfony requirements
echo "\nChecking Symfony requirements...\n";
$output = [];
exec('php bin/console about', $output, $returnCode);
if ($returnCode === 0) {
    echo "✅ Symfony is properly installed.\n";
} else {
    echo "❌ Symfony requirements check failed.\n";
    echo implode("\n", $output) . "\n";
}

echo "\n==========================================================\n";
echo "✨ Setup complete! Next steps:\n";
echo "==========================================================\n";
echo "1. If you need to run migrations: php bin/console doctrine:migrations:migrate\n";
echo "2. Build assets: npm run build\n";
echo "3. Start the application: php -S localhost:8000 -t public/\n";
echo "4. Visit: http://localhost:8000\n";
echo "5. For production deployment, read the security recommendations in README.md\n";
echo "==========================================================\n"; 