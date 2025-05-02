<?php

/**
 * LocalEvent - Environment Configuration Update Script
 */

$envLines = [
    'DATABASE_URL="postgresql://postgres:SYS@127.0.0.1:5432/localevent?serverVersion=14&charset=utf8"',
    '# DATABASE_URL="pgsql://postgres:SYS@127.0.0.1:5432/localevent"',
    'APP_ENV=dev',
    'APP_SECRET=5cb7c59cfb1f30f6aab668348c86d09f',
    'MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0',
    'MAILER_DSN=null://null',
];

echo "Updating .env.local with proper PostgreSQL configuration...\n";

// Write to .env.local
file_put_contents('.env.local', implode("\n", $envLines));

echo "Environment configuration updated successfully!\n";
echo "Key changes:\n";
echo "- Updated PostgreSQL connection string with proper server version\n";
echo "- Set charset to utf8\n";
echo "- Configured for development environment\n";

echo "\nPlease run 'php bin/console cache:clear' to apply changes.\n"; 