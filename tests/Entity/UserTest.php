<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Ticket;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('securePassword');

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
        $this->assertSame('securePassword', $user->getPassword());
        $this->assertNull($user->getId());
    }

    public function testUserTicketsRelation(): void
    {
        $user = new User();
        $ticket = new Ticket();

        $this->assertCount(0, $user->getTickets());

        $user->addTicket($ticket);
        $this->assertCount(1, $user->getTickets());
        $this->assertTrue($user->getTickets()->contains($ticket));
        $this->assertSame($user, $ticket->getOwner());

        $user->removeTicket($ticket);
        $this->assertCount(0, $user->getTickets());
        $this->assertNull($ticket->getOwner());
    }

    public function testUserAssignedTicketsRelation(): void
    {
        $user = new User();
        $ticket = new Ticket();

        $this->assertCount(0, $user->getAssignedTickets());

        $user->addAssignedTicket($ticket);
        $this->assertCount(1, $user->getAssignedTickets());
        $this->assertTrue($user->getAssignedTickets()->contains($ticket));
        $this->assertSame($user, $ticket->getAssignee());

        $user->removeAssignedTicket($ticket);
        $this->assertCount(0, $user->getAssignedTickets());
        $this->assertNull($ticket->getAssignee());
    }

    public function testUsernameIsEmail(): void
    {
        $user = new User();
        $user->setEmail('dev@example.com');
        $this->assertSame('dev@example.com', $user->getUserIdentifier());
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        $this->assertNull($user->eraseCredentials()); // pas de données sensibles
    }

    public function testRolesAlwaysIncludesUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testDuplicateTicketsNotAdded(): void
    {
        $user = new User();
        $ticket = new Ticket();

        $user->addTicket($ticket);
        $user->addTicket($ticket);

        $this->assertCount(1, $user->getTickets(), 'Ticket ne doit être ajouté qu’une fois');
    }

    public function testDuplicateAssignedTicketsNotAdded(): void
    {
        $user = new User();
        $ticket = new Ticket();

        $user->addAssignedTicket($ticket);
        $user->addAssignedTicket($ticket);

        $this->assertCount(1, $user->getAssignedTickets(), 'Ticket assigné ne doit être ajouté qu’une fois');
    }

    public function testRemoveNonExistentTicketDoesNothing(): void
    {
        $user = new User();
        $ticket = new Ticket();

        $user->removeTicket($ticket); // ne jette pas d’erreur
        $this->assertCount(0, $user->getTickets());
    }

    public function testRemoveNonExistentAssignedTicketDoesNothing(): void
    {
        $user = new User();
        $ticket = new Ticket();

        $user->removeAssignedTicket($ticket);
        $this->assertCount(0, $user->getAssignedTickets());
    }
}
