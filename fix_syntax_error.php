<?php
// Fix syntax error in Event.php

$eventEntityPath = __DIR__ . '/src/Entity/Event.php';
echo "Fixing syntax error in Event.php...\n";

// Create a simpler Event entity to fix the syntax issues
$fixedEventContent = '<?php

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

    #[ORM\ManyToOne(inversedBy: "events")]
    #[ORM\JoinColumn(name: "created_by", nullable: true)]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;
    
    #[ORM\Column(options: {"default" : false})]
    private bool $isApproved = false;

    #[ORM\OneToMany(mappedBy: "event", targetEntity: Rsvp::class, orphanRemoval: true)]
    private Collection $rsvps;

    #[ORM\OneToMany(mappedBy: "event", targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    public function __construct()
    {
        $this->rsvps = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->createdAt = new \DateTime();
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
    
    public function getIsApproved(): bool
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
}';

// Backup the original file
file_put_contents($eventEntityPath . '.bak3', file_get_contents($eventEntityPath));
echo "Created backup of Event entity at: {$eventEntityPath}.bak3\n";

// Write the fixed content
file_put_contents($eventEntityPath, $fixedEventContent);
echo "✅ Fixed syntax error in Event.php\n";

echo "\nNow let's create a helper Repository class for proper Doctrine mapping:\n";

// Create/update repository classes
$rsvpRepoPath = __DIR__ . '/src/Repository/RsvpRepository.php';
$rsvpRepoContent = '<?php

namespace App\Repository;

use App\Entity\Rsvp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rsvp>
 */
class RsvpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rsvp::class);
    }

    public function save(Rsvp $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Rsvp $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}';

file_put_contents($rsvpRepoPath, $rsvpRepoContent);
echo "✅ Created/Updated RsvpRepository.php\n";

// Create database patch to ensure columns exist
$sqlPatchPath = __DIR__ . '/database_patch.sql';
$sqlPatchContent = "-- SQL patch to ensure database structure works with our entities

-- Ensure is_approved column exists in event table
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='event' AND column_name='is_approved'
    ) THEN
        ALTER TABLE event ADD COLUMN is_approved BOOLEAN DEFAULT false;
    END IF;
END
$$;

-- Add indexes for better performance and relationship integrity
CREATE INDEX IF NOT EXISTS idx_event_category ON event(category);
CREATE INDEX IF NOT EXISTS idx_event_created_by ON event(created_by);

-- Update any empty category values to avoid errors
UPDATE event SET category = '' WHERE category IS NULL;
";

file_put_contents($sqlPatchPath, $sqlPatchContent);
echo "✅ Created SQL patch at database_patch.sql\n";

echo "\nNEXT STEPS:\n";
echo "1. Run: php bin/console cache:clear\n";
echo "2. Run: symfony server:start\n";
echo "3. If you still have errors, please check the error message again\n";
?>
