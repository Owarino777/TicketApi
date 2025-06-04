<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Ticket;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
    }

    private function createTicket(): Ticket
    {
        return new Ticket();
    }

    public function testGettersAndSetters(): void
    {
        $this->user->setEmail('test@example.com');
        $this->user->setRoles(['ROLE_USER']);
        $this->user->setPassword('securePassword');

        $this->assertSame('test@example.com', $this->user->getEmail());
        $this->assertSame(['ROLE_USER'], $this->user->getRoles());
        $this->assertSame('securePassword', $this->user->getPassword());
        $this->assertNull($this->user->getId());
    }

    public function testUserTicketsRelation(): void
    {
        $ticket = $this->createTicket();

        $this->assertCount(0, $this->user->getTickets());

        $this->user->addTicket($ticket);
        $this->assertCount(1, $this->user->getTickets());
        $this->assertTrue($this->user->getTickets()->contains($ticket));
        $this->assertSame($this->user, $ticket->getOwner());

        $this->user->removeTicket($ticket);
        $this->assertCount(0, $this->user->getTickets());
        $this->assertNull($ticket->getOwner());
    }

    public function testUserAssignedTicketsRelation(): void
    {
        $ticket = $this->createTicket();

        $this->assertCount(0, $this->user->getAssignedTickets());

        $this->user->addAssignedTicket($ticket);
        $this->assertCount(1, $this->user->getAssignedTickets());
        $this->assertTrue($this->user->getAssignedTickets()->contains($ticket));
        $this->assertSame($this->user, $ticket->getAssignee());

        $this->user->removeAssignedTicket($ticket);
        $this->assertCount(0, $this->user->getAssignedTickets());
        $this->assertNull($ticket->getAssignee());
    }

    public function testUsernameIsEmail(): void
    {
        $this->user->setEmail('dev@example.com');
        $this->assertSame('dev@example.com', $this->user->getUserIdentifier());
    }

    public function testEraseCredentials(): void
    {
        $this->assertNull($this->user->eraseCredentials()); // pas de données sensibles
    }

    public function testRolesAlwaysIncludesUser(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        $this->assertContains('ROLE_USER', $this->user->getRoles());
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());
    }

    public function testDuplicateTicketsNotAdded(): void
    {
        $ticket = $this->createTicket();

        $this->user->addTicket($ticket);
        $this->user->addTicket($ticket);

        $this->assertCount(1, $this->user->getTickets(), 'Ticket ne doit être ajouté qu’une fois');
    }

    public function testDuplicateAssignedTicketsNotAdded(): void
    {
        $ticket = $this->createTicket();

        $this->user->addAssignedTicket($ticket);
        $this->user->addAssignedTicket($ticket);

        $this->assertCount(1, $this->user->getAssignedTickets(), 'Ticket assigné ne doit être ajouté qu’une fois');
    }

    public function testRemoveNonExistentTicketDoesNothing(): void
    {
        $ticket = $this->createTicket();

        $this->user->removeTicket($ticket); // ne jette pas d’erreur
        $this->assertCount(0, $this->user->getTickets());
    }

    public function testRemoveNonExistentAssignedTicketDoesNothing(): void
    {
        $ticket = $this->createTicket();

        $this->user->removeAssignedTicket($ticket);
        $this->assertCount(0, $this->user->getAssignedTickets());
    }
}
