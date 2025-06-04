<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use PHPUnit\Framework\TestCase;

class TicketTest extends TestCase
{
    private Ticket $ticket;
    private User $owner;
    private User $assignee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticket = new Ticket();
        $this->owner = new User();
        $this->assignee = new User();
    }

    private function createComment(): Comment
    {
        return new Comment();
    }

    public function testGettersAndSetters(): void
    {
        $this->ticket
            ->setTitle('Incident critique')
            ->setDescription('La machine est HS')
            ->setPriority(TicketPriority::HIGH)
            ->setOwner($this->owner)
            ->setAssignee($this->assignee);

        $this->assertSame('Incident critique', $this->ticket->getTitle());
        $this->assertSame('La machine est HS', $this->ticket->getDescription());
        $this->assertSame(TicketPriority::HIGH, $this->ticket->getPriority());
        $this->assertSame(TicketStatus::WAITING, $this->ticket->getStatus());
        $this->assertSame($this->owner, $this->ticket->getOwner());
        $this->assertSame($this->assignee, $this->ticket->getAssignee());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->ticket->getCreatedAt());
        $this->assertNotNull($this->ticket->getAssignedAtFirst());
        $this->assertNotNull($this->ticket->getAssignedAtLast());
    }

    public function testAddAndRemoveComment(): void
    {
        $comment = $this->createComment();
        $this->ticket->addComment($comment);

        $this->assertCount(1, $this->ticket->getComments());
        $this->assertSame($this->ticket, $comment->getTicket());

        $this->ticket->removeComment($comment);
        $this->assertCount(0, $this->ticket->getComments());
        $this->assertNull($comment->getTicket());
    }

    public function testCreatedAtIsImmutable(): void
    {
        $createdAt = $this->ticket->getCreatedAt();
        $this->expectException(\BadMethodCallException::class);
        $this->ticket->setCreatedAt(new \DateTimeImmutable('2020-01-01'));
        $this->assertSame($createdAt, $this->ticket->getCreatedAt());
    }

    public function testStatusSetterOnlyAcceptsEnum(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        $this->ticket->setStatus('closed');
    }

    public function testPrioritySetterOnlyAcceptsEnum(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        $this->ticket->setPriority('fake');
    }

    public function testToStringReturnsTitle(): void
    {
        $this->ticket->setTitle('Test Ticket');
        $this->assertSame('Test Ticket', (string) $this->ticket);
    }
}
