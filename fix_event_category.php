<?php
// Script to fix the Event entity's category field

$eventEntityPath = __DIR__ . '/src/Entity/Event.php';
echo "Fixing the category field in the Event entity to match the database schema...\n";

// Read the current content
$currentContent = file_get_contents($eventEntityPath);

// Create a completely new Event entity file that matches your exact database structure
$newEventContent = '<?php

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

    // This matches your database schema exactly - category is a string column
    #[ORM\Column(length: 100, nullable: true, name: "category")]
    private ?string $categoryName = null;
    
    #[ORM\Column(nullable: true, name: "category_id")]
    private ?int $categoryId = null;

    #[ORM\ManyToOne(inversedBy: "events")]
    #[ORM\JoinColumn(name: "created_by", nullable: true, referencedColumnName: "id")]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;
    
    #[ORM\Column(type: "boolean", options: {"default" : false})]
    private ?bool $isApproved = false;

    #[ORM\OneToMany(mappedBy: "event", targetEntity: Rsvp::class, orphanRemoval: true)]
    private Collection $rsvps;

    #[ORM\OneToMany(mappedBy: "event", targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    // Add a ManyToMany relationship with Category
    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: "event_category")]
    #[ORM\JoinColumn(name: "event_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "category_id", referencedColumnName: "id")]
    private Collection $categories;

    public function __construct()
    {
        $this->rsvps = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->categories = new ArrayCollection();
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

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function setCategoryName(?string $categoryName): static
    {
        $this->categoryName = $categoryName;

        return $this;
    }
    
    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): static
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    // For backward compatibility
    public function getCategory(): ?string
    {
        return $this->categoryName;
    }

    public function setCategory(?string $category): static
    {
        $this->categoryName = $category;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

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
    
    public function getIsApproved(): ?bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): static
    {
        $this->isApproved = $isApproved;

        return $this;
    }

    /**
     * @return Collection<int, Rsvp>
     */
    public function getRsvps(): Collection
    {
        return $this->rsvps;
    }

    public function addRsvp(Rsvp $rsvp): static
    {
        if (!$this->rsvps->contains($rsvp)) {
            $this->rsvps->add($rsvp);
            $rsvp->setEvent($this);
        }

        return $this;
    }

    public function removeRsvp(Rsvp $rsvp): static
    {
        if ($this->rsvps->removeElement($rsvp)) {
            // set the owning side to null (unless already changed)
            if ($rsvp->getEvent() === $this) {
                $rsvp->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setEvent($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getEvent() === $this) {
                $comment->setEvent(null);
            }
        }

        return $this;
    }
    
    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            // Update the string category name for legacy support
            $this->categoryName = $category->getName();
            $this->categoryId = $category->getId();
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }
}';

// Backup the original file
file_put_contents($eventEntityPath . '.bak2', $currentContent);
echo "Created backup of Event entity at: {$eventEntityPath}.bak2\n";

// Write the new content
file_put_contents($eventEntityPath, $newEventContent);
echo "✅ Updated Event entity to properly map category fields.\n";

// Also fix the Category entity to make sure it's properly defined
$categoryEntityPath = __DIR__ . '/src/Entity/Category.php';
echo "\nFixing the Category entity to match the database schema...\n";

$newCategoryContent = '<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $name = null;
    
    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: "categories")]
    private Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
    
    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->addCategory($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            $event->removeCategory($this);
        }

        return $this;
    }
}';

// Backup the original file
if (file_exists($categoryEntityPath)) {
    file_put_contents($categoryEntityPath . '.bak2', file_get_contents($categoryEntityPath));
    echo "Created backup of Category entity at: {$categoryEntityPath}.bak2\n";
}

// Write the new content
file_put_contents($categoryEntityPath, $newCategoryContent);
echo "✅ Updated Category entity.\n";

// Now create a SQL script to directly fix any potential issues in the database
$fixSqlPath = __DIR__ . '/fix_database.sql';
$sqlContent = "-- SQL Script to fix potential database issues
-- Run this if you still have problems after the entity fixes

-- Add category_id column to event table if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='event' AND column_name='category_id') THEN
        ALTER TABLE event ADD COLUMN category_id INTEGER;
    END IF;
END $$;

-- Make sure event table has is_approved column
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='event' AND column_name='is_approved') THEN
        ALTER TABLE event ADD COLUMN is_approved BOOLEAN DEFAULT false;
    END IF;
END $$;

-- Fix any NULL category values to prevent errors
UPDATE event SET category = '' WHERE category IS NULL;

-- Make sure event_category table has correct constraints
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_name='event_category' AND constraint_type='PRIMARY KEY') THEN
        -- Drop and recreate the table with proper constraints if needed
        DROP TABLE IF EXISTS event_category;
        CREATE TABLE event_category (
            event_id INT REFERENCES event(id) ON DELETE CASCADE,
            category_id INT REFERENCES category(id) ON DELETE CASCADE,
            PRIMARY KEY (event_id, category_id)
        );
    END IF;
END $$;
";

file_put_contents($fixSqlPath, $sqlContent);
echo "\n✅ Created SQL fix script: fix_database.sql\n";

echo "\nNEXT STEPS:\n";
echo "1. Run: php bin/console cache:clear\n";
echo "2. Run: symfony server:start\n";
echo "3. If you still have issues, run the SQL fix script in PostgreSQL\n";
?>
