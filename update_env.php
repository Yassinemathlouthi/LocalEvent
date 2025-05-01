<?php
$envFile = '.env';
$content = file_get_contents($envFile);

// Replace the database URL
$content = str_replace(
    'DATABASE_URL="pgsql://postgres:SYS@127.0.0.1:5432/localevent"',
    'DATABASE_URL="postgresql://postgres:SYS@127.0.0.1:5432/localevent"',
    $content
);

file_put_contents($envFile, $content);

echo "Updated .env file with the correct PostgreSQL URL format.\n";
?>
