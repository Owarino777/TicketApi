<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Ticket;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    public function testCommentSettersAndGetters()
    {
        $author = new User();
        $ticket = new Ticket();
        $now = new \DateTimeImmutable();
        $comment = new Comment();

        $comment
            ->setContent('Problème constaté ce matin')
            ->setCreatedAt($now)
            ->setAuthor($author)
            ->setTicket($ticket);

        $this->assertSame('Problème constaté ce matin', $comment->getContent());
        $this->assertSame($now, $comment->getCreatedAt());
        $this->assertSame($author, $comment->getAuthor());
        $this->assertSame($ticket, $comment->getTicket());
    }

    public function testCommentBelongsToTicket()
    {
        $ticket = new Ticket();
        $comment = new Comment();

        $comment->setTicket($ticket);
        $this->assertSame($ticket, $comment->getTicket());
    }

    public function testCommentBelongsToAuthor()
    {
        $author = new User();
        $comment = new Comment();

        $comment->setAuthor($author);
        $this->assertSame($author, $comment->getAuthor());
    }
}
