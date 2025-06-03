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
        $comment = new Comment();

        $comment->setContent('Un commentaire')
            ->setAuthor($author)
            ->setTicket($ticket);

        $this->assertSame('Un commentaire', $comment->getContent());
        $this->assertSame($author, $comment->getAuthor());
        $this->assertSame($ticket, $comment->getTicket());
    }
}
