<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[Vich\Uploadable]
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

    #[ORM\ManyToOne(inversedBy: 'organizedEvents')]
    #[ORM\JoinColumn(name: 'organizer_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $organizer = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $created_at = null;
    
    #[ORM\Column(nullable: true, options: ["default" => true])]
    private ?bool $is_approved = true;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Rsvp::class, orphanRemoval: true)]
    private Collection $rsvps;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Attendance::class, orphanRemoval: true)]
    private Collection $attendances;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'events')]
    #[ORM\JoinTable(name: 'event_category')]
    private Collection $categories;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $image = null;

    #[Vich\UploadableField(mapping: 'event_images', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->rsvps = new ArrayCollection();
        $this->attendances = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->created_at = new \DateTime();
        $this->is_approved = true;
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

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
    
    public function getIsApproved(): ?bool
    {
        return $this->is_approved;
    }

    public function setIsApproved(?bool $is_approved): static
    {
        $this->is_approved = $is_approved;

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
     * @return Collection<int, Attendance>
     */
    public function getAttendances(): Collection
    {
        return $this->attendances;
    }

    public function addAttendance(Attendance $attendance): static
    {
        if (!$this->attendances->contains($attendance)) {
            $this->attendances->add($attendance);
            $attendance->setEvent($this);
        }

        return $this;
    }

    public function removeAttendance(Attendance $attendance): static
    {
        if ($this->attendances->removeElement($attendance)) {
            // set the owning side to null (unless already changed)
            if ($attendance->getEvent() === $this) {
                $attendance->setEvent(null);
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
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @param string|null $image
     * @return $this
     */
    public function setImage(?string $image): static
    {
        $this->image = $image;
        
        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): self
    {
        $this->imageFile = $imageFile;
        
        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        
        return $this;
    }

    /**
     * @see \Serializable::serialize()
     */
    public function __sleep()
    {
        // Do not serialize the file
        $vars = get_object_vars($this);
        unset($vars['imageFile']);
        
        return array_keys($vars);
    }
}