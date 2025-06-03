<?php

namespace App\Tests\Entity;

use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Comment;
use PHPUnit\Framework\TestCase;

class TicketTest extends TestCase
{
    public function testTicketSettersAndGetters()
    {
        $owner = new User();
        $assignee = new User();

        $ticket = new Ticket();
        $ticket->setTitle('Titre')
            ->setDescription('Desc')
            ->setPriority('basse')
            ->setStatus('pending')
            ->setOwner($owner)
            ->setAssignee($assignee);

        $this->assertSame('Titre', $ticket->getTitle());
        $this->assertSame('Desc', $ticket->getDescription());
        $this->assertSame('basse', $ticket->getPriority());
        $this->assertSame('pending', $ticket->getStatus());
        $this->assertSame($owner, $ticket->getOwner());
        $this->assertSame($assignee, $ticket->getAssignee());
    }

    public function testTicketCommentsRelation()
    {
        $ticket = new Ticket();
        $comment = new Comment();
        $ticket->addComment($comment);

        $this->assertTrue($ticket->getComments()->contains($comment));
        $this->assertSame($ticket, $comment->getTicket());

        // Suppression du commentaire
        $ticket->removeComment($comment);
        $this->assertFalse($ticket->getComments()->contains($comment));
        // À vérifier côté Comment : $comment->getTicket() === null si removeComment() gère bien le "orphan"
    }

    public function testTicketEnumPriorityAndStatus()
    {
        $ticket = new Ticket();
        $ticket->setPriority('normale');
        $ticket->setStatus('done');

        $this->assertContains($ticket->getPriority(), ['basse', 'normale', 'haute']);
        $this->assertContains($ticket->getStatus(), ['pending', 'waiting', 'in_progress', 'done']);
    }
}
