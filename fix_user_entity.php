<?php
// Script to fix the User entity syntax error

$userEntityPath = __DIR__ . '/src/Entity/User.php';
echo "Fixing User entity syntax...\n";

// Read the current content of the User entity
$content = file_get_contents($userEntityPath);

// Backup the original file
file_put_contents($userEntityPath . '.bak', $content);
echo "Created backup of User entity at: " . $userEntityPath . '.bak' . "\n";

// Add the Table annotation properly (without breaking the class declaration)
if (strpos($content, '@ORM\Table(name="app_user")') === false) {
    // Add proper table annotation before the class
    $content = preg_replace(
        '/(namespace.*?;.*?)(\s*)(#\[ORM\\\\Entity|\/\*\*|class\s+User)/s',
        "$1$2/**\n * @ORM\\Table(name=\"app_user\")\n */$2$3",
        $content
    );
}

// Fix any other broken syntax if needed
file_put_contents($userEntityPath, $content);
echo "Updated User entity with proper table annotation.\n";

echo "\nNow clear the cache again with: php bin/console cache:clear\n";
?>
