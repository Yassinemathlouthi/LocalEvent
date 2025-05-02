<?php
// Script to update Symfony Entity classes to match the exact database schema

function updateEntityFile($filePath, $replacePatterns) {
    if (!file_exists($filePath)) {
        echo "❌ File not found: $filePath\n";
        return false;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    foreach ($replacePatterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "✅ Updated: $filePath\n";
        return true;
    } else {
        echo "⚠️ No changes needed in: $filePath\n";
        return false;
    }
}

echo "===== ENTITY SYNCHRONIZATION WITH DATABASE SCHEMA =====\n\n";

// 1. Fix User entity - ensure it uses app_user table name
$userEntityPath = __DIR__ . '/src/Entity/User.php';
echo "1. Updating User entity to use app_user table...\n";
updateEntityFile($userEntityPath, [
    '/class User(?!.*@ORM\\\\Table)/s' => 'class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Table(name="app_user")
     */',
    '/@ORM\\\\Table\(name="[^"]+"\)/i' => '@ORM\Table(name="app_user")',
    '/@ORM\\\\Column\(type="json"\)/i' => '@ORM\Column(type="json", nullable=true)',
    '/@ORM\\\\Column\(.*name="interests".*\)/i' => '@ORM\Column(type="json", nullable=true, name="roles")'
]);

// 2. Fix Event entity - adjust for the exact fields in database
$eventEntityPath = __DIR__ . '/src/Entity/Event.php';
echo "\n2. Updating Event entity to match database structure...\n";
updateEntityFile($eventEntityPath, [
    '/@ORM\\\\JoinColumn\(name="category_id".*\)/i' => '@ORM\Column(type="string", length=100, nullable=true, name="category")',
    '/@ORM\\\\ManyToOne\(targetEntity=Category::class.*\)[\r\n\s]+.*@ORM\\\\JoinColumn.*[\r\n\s]+private (?:Category|Object|mixed|)\s+\$category/is' => '@ORM\Column(type="string", length=100, nullable=true, name="category")
    private ?string $category = null',
    '/@ORM\\\\JoinColumn\(name="organizer_id".*\)/i' => '@ORM\JoinColumn(name="created_by", nullable=true)',
    '/@ORM\\\\Column\(type="datetime.*name="date".*\)/i' => '@ORM\Column(type="date", nullable=false, name="date")', 
    '/private \?\\\\DateTime(?:Interface|Immutable|) \$date/i' => 'private ?\\DateTimeInterface $date',
    '/private \$isApproved/i' => 'private $status',
    '/@ORM\\\\Column\(type="boolean".*name="is_approved".*\)/i' => '@ORM\Column(type="string", length=20, nullable=true)',
    '/@ORM\\\\OneToMany\(targetEntity=Attendance::class.*\)/i' => '@ORM\OneToMany(targetEntity=Rsvp::class, mappedBy="event", orphanRemoval=true)'
]);

// 3. Fix/Create Rsvp entity (instead of Attendance)
$rsvpEntityPath = __DIR__ . '/src/Entity/Rsvp.php';
$attendanceEntityPath = __DIR__ . '/src/Entity/Attendance.php';

if (file_exists($attendanceEntityPath) && !file_exists($rsvpEntityPath)) {
    echo "\n3. Converting Attendance entity to Rsvp entity...\n";
    $attendanceContent = file_get_contents($attendanceEntityPath);
    
    // Replace class name and references
    $rsvpContent = str_replace(
        ['class Attendance', 'Attendance implements', '$attendance', 'attendance'],
        ['class Rsvp', 'Rsvp implements', '$rsvp', 'rsvp'],
        $attendanceContent
    );
    
    // Update table name annotation
    $rsvpContent = preg_replace(
        '/@ORM\\\\Table\(name="[^"]+"\)/i', 
        '@ORM\Table(name="rsvp")',
        $rsvpContent
    );
    
    // Update properties
    $rsvpContent = preg_replace(
        '/@ORM\\\\Column\(.*name="joined_at".*\)/i',
        '@ORM\Column(type="datetime", nullable=true, name="responded_at")',
        $rsvpContent
    );
    
    $rsvpContent = preg_replace(
        '/private \$joinedAt/i',
        'private $respondedAt',
        $rsvpContent
    );
    
    // Save the new Rsvp entity
    file_put_contents($rsvpEntityPath, $rsvpContent);
    echo "✅ Created: $rsvpEntityPath\n";
    
    // Optionally rename the old file to prevent confusion
    rename($attendanceEntityPath, $attendanceEntityPath . '.bak');
    echo "✅ Renamed old Attendance entity to: " . $attendanceEntityPath . '.bak' . "\n";
} else if (file_exists($rsvpEntityPath)) {
    echo "\n3. Rsvp entity already exists. Updating to match database structure...\n";
    updateEntityFile($rsvpEntityPath, [
        '/@ORM\\\\Table\(name="[^"]+"\)/i' => '@ORM\Table(name="rsvp")',
        '/@ORM\\\\Column\(.*name="joined_at".*\)/i' => '@ORM\Column(type="datetime", nullable=true, name="responded_at")',
        '/private \$joinedAt/i' => 'private $respondedAt',
        '/getJoinedAt/i' => 'getRespondedAt',
        '/setJoinedAt/i' => 'setRespondedAt',
        '/@ORM\\\\Column\(.*name="status".*\)/i' => '@ORM\Column(type="string", length=20, options={"default":"interested"}, nullable=false)'
    ]);
} else {
    echo "\n3. ❌ Neither Attendance nor Rsvp entity found.\n";
}

// 4. Check and fix Category entity
$categoryEntityPath = __DIR__ . '/src/Entity/Category.php';
echo "\n4. Updating Category entity to match database schema...\n";
updateEntityFile($categoryEntityPath, [
    '/@ORM\\\\Column\(.*name="name".*\)/i' => '@ORM\Column(type="string", length=50, unique=true, nullable=false)'
]);

echo "\n===== ENTITY SYNCHRONIZATION COMPLETE =====\n";
echo "\nNEXT STEPS:\n";
echo "1. Review the updated entity files in src/Entity/\n";
echo "2. Run: php bin/console cache:clear\n";
echo "3. Run: symfony server:start\n";
echo "4. Test the application in your browser\n";
?>
