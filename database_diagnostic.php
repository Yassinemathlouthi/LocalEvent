<?php
// Direct diagnostic connection to PostgreSQL database

// Database connection parameters - using values from .env
$host = '127.0.0.1';
$port = '5432';
$dbname = 'localevent';
$user = 'postgres';
$password = 'SYS';

echo "🔍 POSTGRESQL DATABASE DIAGNOSTIC TOOL\n";
echo "=================================\n\n";

// Try to connect using PDO
echo "Attempting direct PDO connection to PostgreSQL...\n";
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password";
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connection successful!\n\n";

    // Get database info
    echo "PostgreSQL Version: " . $pdo->query('SELECT version()')->fetchColumn() . "\n\n";
    
    // List all tables
    echo "📋 DATABASE TABLES:\n";
    echo "=================\n";
    $tables = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found in the database!\n";
    } else {
        foreach ($tables as $table) {
            echo "- $table\n";
            
            // Get column information for each table
            echo "  Columns:\n";
            $columns = $pdo->query("
                SELECT column_name, data_type, is_nullable 
                FROM information_schema.columns 
                WHERE table_name = '$table' 
                ORDER BY ordinal_position
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $column) {
                $nullable = $column['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL';
                echo "    • {$column['column_name']} ({$column['data_type']}) $nullable\n";
            }
            echo "\n";
        }
    }

    // Check the 'event' table specifically for our problem fields
    if (in_array('event', $tables)) {
        echo "🔍 DETAILED CHECK OF EVENT TABLE:\n";
        echo "=============================\n";
        
        // Check if category column exists
        $checkCategory = $pdo->query("
            SELECT 1 FROM information_schema.columns 
            WHERE table_name = 'event' AND column_name = 'category'
        ")->fetchColumn();
        
        echo "category column exists: " . ($checkCategory ? "YES" : "NO") . "\n";
        
        if ($checkCategory) {
            // Check null values in category
            $nullCategoryCount = $pdo->query("SELECT COUNT(*) FROM event WHERE category IS NULL")->fetchColumn();
            echo "Null values in category: $nullCategoryCount\n";
            
            // Check types of values in category 
            $samples = $pdo->query("SELECT category FROM event LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
            echo "Sample category values: " . implode(", ", $samples) . "\n";
        }
        
        // Check if is_approved column exists
        $checkIsApproved = $pdo->query("
            SELECT 1 FROM information_schema.columns 
            WHERE table_name = 'event' AND column_name = 'is_approved'
        ")->fetchColumn();
        
        echo "is_approved column exists: " . ($checkIsApproved ? "YES" : "NO") . "\n";
    }
    
    // Create and execute schema fix SQL
    echo "\n📝 EXECUTING DATABASE SCHEMA FIXES:\n";
    echo "==============================\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Ensure is_approved exists in event table
        if (in_array('event', $tables)) {
            if (!$pdo->query("
                SELECT 1 FROM information_schema.columns 
                WHERE table_name = 'event' AND column_name = 'is_approved'
            ")->fetchColumn()) {
                echo "Adding missing is_approved column to event table...\n";
                $pdo->exec("ALTER TABLE event ADD COLUMN is_approved BOOLEAN DEFAULT false");
                echo "✅ Added is_approved column\n";
            }
            
            // Update null category values
            $nullCategoryCount = $pdo->query("SELECT COUNT(*) FROM event WHERE category IS NULL")->fetchColumn();
            if ($nullCategoryCount > 0) {
                echo "Fixing $nullCategoryCount null category values...\n";
                $pdo->exec("UPDATE event SET category = '' WHERE category IS NULL");
                echo "✅ Fixed null category values\n";
            }
        }
        
        // Commit all changes
        $pdo->commit();
        echo "✅ All schema fixes applied successfully\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "❌ Error applying schema fixes: " . $e->getMessage() . "\n";
    }
    
    // Create minimal entity class
    echo "\n🔧 CREATING MINIMALIST ENTITY CLASSES:\n";
    echo "================================\n";

    // Clean up and create minimal Event entity
    $eventEntityPath = __DIR__ . '/src/Entity/Event.php';
    $eventEntityContent = '<?php

namespace App\Entity;

use App\Repository\EventRepository;
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

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;
    
    #[ORM\Column(nullable: true)]
    private ?bool $isApproved = false;

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

    public function setIsApproved(?bool $isApproved): static
    {
        $this->isApproved = $isApproved;

        return $this;
    }
}';

    file_put_contents($eventEntityPath, $eventEntityContent);
    echo "✅ Created minimalist Event entity\n";
    
    // Create minimal repository
    $eventRepoPath = __DIR__ . '/src/Repository/EventRepository.php';
    $eventRepoContent = '<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }
}';

    file_put_contents($eventRepoPath, $eventRepoContent);
    echo "✅ Created minimalist EventRepository\n";
    
    // Ensure Category entity is minimal and correct
    $categoryEntityPath = __DIR__ . '/src/Entity/Category.php';
    $categoryEntityContent = '<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

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

    file_put_contents($categoryEntityPath, $categoryEntityContent);
    echo "✅ Created minimalist Category entity\n";
    
    // Create minimal Category repository
    $categoryRepoPath = __DIR__ . '/src/Repository/CategoryRepository.php';
    $categoryRepoContent = '<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }
}';

    file_put_contents($categoryRepoPath, $categoryRepoContent);
    echo "✅ Created minimalist CategoryRepository\n";
    
    // Create minimalist User entity
    $userEntityPath = __DIR__ . '/src/Entity/User.php';
    $userEntityContent = '<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "app_user")]
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

    #[ORM\Column]
    private ?string $password = null;

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
    }
}';

    file_put_contents($userEntityPath, $userEntityContent);
    echo "✅ Created minimalist User entity\n";
    
    // Create minimal User repository
    $userRepoPath = __DIR__ . '/src/Repository/UserRepository.php';
    $userRepoContent = '<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
}';

    file_put_contents($userRepoPath, $userRepoContent);
    echo "✅ Created minimalist UserRepository\n";

    echo "\n✅ All diagnostics and fixes completed successfully!\n";
    echo "Run 'php bin/console cache:clear' and then 'symfony server:start'\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database credentials and make sure PostgreSQL server is running.\n";
}
?>
