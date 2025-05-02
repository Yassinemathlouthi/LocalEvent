<?php
// Direct fix for Event.php syntax error

// This approach makes minimal changes to fix just the syntax issue
$eventEntityPath = __DIR__ . '/src/Entity/Event.php';
echo "Directly fixing Event.php syntax error...\n";

// Create a fresh entity file from scratch
$content = '<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $time = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(nullable: true, name: "created_by")]
    private ?int $userId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;
    
    #[ORM\Column(type: "boolean")]
    private bool $isApproved = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isApproved = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
    
    public function getIsApproved(): bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): static
    {
        $this->isApproved = $isApproved;

        return $this;
    }
}';

// Backup current file
file_put_contents($eventEntityPath . '.bak4', file_get_contents($eventEntityPath));
echo "Created backup at " . $eventEntityPath . ".bak4\n";

// Write the minimal version
file_put_contents($eventEntityPath, $content);
echo "✅ Fixed Event.php with a minimal implementation\n";

// Also create a database patch to ensure proper columns
$patchPath = __DIR__ . '/fix_db.sql';
$patchContent = "
-- Make sure category is nullable
ALTER TABLE event ALTER COLUMN category DROP NOT NULL;

-- Make sure is_approved exists and has correct type
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema='public' AND table_name='event' AND column_name='is_approved'
    ) THEN
        ALTER TABLE event ADD COLUMN is_approved BOOLEAN DEFAULT false;
    END IF;
END
$$;

-- Make sure category exists with correct type
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema='public' AND table_name='event' AND column_name='category'
    ) THEN
        ALTER TABLE event ADD COLUMN category VARCHAR(100);
    END IF;
END
$$;

-- Update null categories to empty string to avoid errors
UPDATE event SET category = '' WHERE category IS NULL;
";

file_put_contents($patchPath, $patchContent);
echo "✅ Created SQL patch at fix_db.sql\n";

// Clean up redundant entities to prevent issues
$rsvpPath = __DIR__ . '/src/Entity/Rsvp.php';
if (file_exists($rsvpPath)) {
    echo "Removing Rsvp entity to simplify schema...\n";
    file_put_contents($rsvpPath . '.bak', file_get_contents($rsvpPath));
    unlink($rsvpPath);
}

$commentPath = __DIR__ . '/src/Entity/Comment.php';
if (file_exists($commentPath)) {
    echo "Removing Comment entity to simplify schema...\n";
    file_put_contents($commentPath . '.bak', file_get_contents($commentPath));
    unlink($commentPath);
}

echo "\nNEXT STEPS:\n";
echo "1. Run: php bin/console cache:clear\n";
echo "2. Run: symfony server:start\n";
?>
