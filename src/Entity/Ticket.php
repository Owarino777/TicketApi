<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Put(security: "is_granted('ROLE_USER')"),
        new Delete(security: "is_granted('ROLE_USER')")
    ]
)]
#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column(enumType: TicketPriority::class)]
    private ?TicketPriority $priority = null;

    #[ORM\Column(enumType: TicketStatus::class)]
    private ?TicketStatus $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $owner = null;

    #[ORM\ManyToOne(inversedBy: 'assignedTickets')]
    private ?User $assignee = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $assignedAtFirst = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $assignedAtLast = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'ticket')]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = TicketStatus::PENDING;
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

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriority(): ?TicketPriority
    {
        return $this->priority;
    }

    public function setPriority(TicketPriority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStatus(): ?TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        if ($this->createdAt !== null) {
            throw new \BadMethodCallException('createdAt is immutable');
        }

        $this->createdAt = $createdAt;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): static
    {
        $this->assignee = $assignee;

        if (null !== $assignee) {
            $now = new \DateTimeImmutable();
            $this->assignedAtLast = $now;
            if (null === $this->assignedAtFirst) {
                $this->assignedAtFirst = $now;
            }
            if ($this->status === TicketStatus::PENDING) {
                $this->status = TicketStatus::WAITING;
            }
        }

        return $this;
    }

    public function getAssignedAtFirst(): ?\DateTimeImmutable
    {
        return $this->assignedAtFirst;
    }

    public function setAssignedAtFirst(?\DateTimeImmutable $assignedAtFirst): static
    {
        $this->assignedAtFirst = $assignedAtFirst;

        return $this;
    }

    public function getAssignedAtLast(): ?\DateTimeImmutable
    {
        return $this->assignedAtLast;
    }

    public function setAssignedAtLast(?\DateTimeImmutable $assignedAtLast): static
    {
        $this->assignedAtLast = $assignedAtLast;

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
            $comment->setTicket($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getTicket() === $this) {
                $comment->setTicket(null);
            }
        }

        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignee;
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }
}
