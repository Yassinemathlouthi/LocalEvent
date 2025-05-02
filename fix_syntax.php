<?php
// Fix the User entity syntax error

$userEntityPath = __DIR__ . '/src/Entity/User.php';
echo "Fixing User.php syntax error...\n";

// Create a correct version of the file
$correctContent = '<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "app_user")]
#[UniqueEntity(fields: ["email"], message: "There is already an account with this email")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $profile_picture = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: "created_by", targetEntity: Event::class)]
    private Collection $events;

    #[ORM\OneToMany(mappedBy: "user", targetEntity: Rsvp::class, orphanRemoval: true)]
    private Collection $rsvps;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->rsvps = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = "ROLE_USER";

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profile_picture;
    }

    public function setProfilePicture(?string $profile_picture): static
    {
        $this->profile_picture = $profile_picture;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
            $event->setCreatedBy($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getCreatedBy() === $this) {
                $event->setCreatedBy(null);
            }
        }

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
            $rsvp->setUser($this);
        }

        return $this;
    }

    public function removeRsvp(Rsvp $rsvp): static
    {
        if ($this->rsvps->removeElement($rsvp)) {
            // set the owning side to null (unless already changed)
            if ($rsvp->getUser() === $this) {
                $rsvp->setUser(null);
            }
        }

        return $this;
    }
}';

// Backup the original file
file_put_contents($userEntityPath . '.bak2', file_get_contents($userEntityPath));
echo "Created backup of User entity at: " . $userEntityPath . '.bak2' . "\n";

// Write the corrected content
file_put_contents($userEntityPath, $correctContent);
echo "✅ Fixed User.php with proper syntax.\n";

// Now let's also create a fixed Event entity that matches the database schema
$eventEntityPath = __DIR__ . '/src/Entity/Event.php';
echo "\nFixing Event.php to match the database schema...\n";

$eventContent = '<?php

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
file_put_contents($eventEntityPath . '.bak', file_get_contents($eventEntityPath));
echo "Created backup of Event entity at: " . $eventEntityPath . '.bak' . "\n";

// Write the corrected content
file_put_contents($eventEntityPath, $eventContent);
echo "✅ Fixed Event.php to match database schema.\n";

// Create Rsvp entity
$rsvpEntityPath = __DIR__ . '/src/Entity/Rsvp.php';
echo "\nCreating Rsvp.php entity to match the database schema...\n";

$rsvpContent = '<?php

namespace App\Entity;

use App\Repository\RsvpRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RsvpRepository::class)]
class Rsvp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: "rsvps")]
    #[ORM\JoinColumn(name: "user_id", nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: "rsvps")]
    #[ORM\JoinColumn(name: "event_id", nullable: true)]
    private ?Event $event = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $respondedAt = null;

    public function __construct()
    {
        $this->respondedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRespondedAt(): ?\DateTimeInterface
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeInterface $respondedAt): static
    {
        $this->respondedAt = $respondedAt;

        return $this;
    }
}';

// Write the file
file_put_contents($rsvpEntityPath, $rsvpContent);
echo "✅ Created Rsvp.php entity.\n";

// Create Comment entity
$commentEntityPath = __DIR__ . '/src/Entity/Comment.php';
echo "\nCreating Comment.php entity to match the database schema...\n";

$commentContent = '<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: "comments")]
    #[ORM\JoinColumn(name: "user_id", nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: "comments")]
    #[ORM\JoinColumn(name: "event_id", nullable: true)]
    private ?Event $event = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

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
}';

// Write the file
file_put_contents($commentEntityPath, $commentContent);
echo "✅ Created Comment.php entity.\n";

// Create Category entity
$categoryEntityPath = __DIR__ . '/src/Entity/Category.php';
echo "\nFixing Category.php entity to match the database schema...\n";

$categoryContent = '<?php

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

    public function __construct()
    {
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
}';

// Backup the original file if it exists
if (file_exists($categoryEntityPath)) {
    file_put_contents($categoryEntityPath . '.bak', file_get_contents($categoryEntityPath));
    echo "Created backup of Category entity at: " . $categoryEntityPath . '.bak' . "\n";
}

// Write the corrected content
file_put_contents($categoryEntityPath, $categoryContent);
echo "✅ Fixed Category.php to match database schema.\n";

// Create repositories if they don't exist
$rsvpRepoPath = __DIR__ . '/src/Repository/RsvpRepository.php';
echo "\nCreating or updating repository classes...\n";

if (!file_exists($rsvpRepoPath)) {
    $rsvpRepoContent = '<?php

namespace App\Repository;

use App\Entity\Rsvp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rsvp>
 *
 * @method Rsvp|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rsvp|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rsvp[]    findAll()
 * @method Rsvp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
    echo "✅ Created RsvpRepository.php\n";
}

$commentRepoPath = __DIR__ . '/src/Repository/CommentRepository.php';
if (!file_exists($commentRepoPath)) {
    $commentRepoContent = '<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function save(Comment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Comment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}';
    file_put_contents($commentRepoPath, $commentRepoContent);
    echo "✅ Created CommentRepository.php\n";
}

echo "\n===== ALL ENTITY FIXES COMPLETE =====\n";
echo "\nNEXT STEPS:\n";
echo "1. Run: php bin/console cache:clear\n";
echo "2. Run: symfony server:start\n";
echo "3. Test the application in your browser\n";
?>
