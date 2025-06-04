<?php

namespace App\Tests\Entity;

use App\Entity\Ticket;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Entity\User;
use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TicketTest extends WebTestCase
{
    public function testTicketSettersAndGetters()
    {
        $owner = new User();
        $assignee = new User();
        $ticket = new Ticket();

        $now = $ticket->getCreatedAt(); // La date assignée à la création
        $later = $now->modify('+1 hour');

        $ticket
            ->setTitle('Incident critique')
            ->setDescription('La machine est HS')
            ->setPriority(TicketPriority::HIGH)
            ->setStatus(TicketStatus::PENDING) // ou WAITING si tu veux tester la transition auto plus bas
            ->setOwner($owner)
            ->setAssignee($assignee);

        $this->assertSame('Incident critique', $ticket->getTitle());
        $this->assertSame('La machine est HS', $ticket->getDescription());
        $this->assertSame(TicketPriority::HIGH, $ticket->getPriority());
        $this->assertSame(TicketStatus::WAITING, $ticket->getStatus()); // logique auto dans setAssignee()
        $this->assertSame($now, $ticket->getCreatedAt());
        $this->assertSame($owner, $ticket->getOwner());
        $this->assertSame($assignee, $ticket->getAssignee());
        $this->assertNotNull($ticket->getAssignedAtFirst());
        $this->assertNotNull($ticket->getAssignedAtLast());
    }

    public function testTicketAddAndRemoveComment()
    {
        $ticket = new Ticket();
        $comment = new Comment();

        // Ajout du commentaire
        $ticket->addComment($comment);
        $this->assertCount(1, $ticket->getComments());
        $this->assertSame($ticket, $comment->getTicket());

        // Suppression du commentaire
        $ticket->removeComment($comment);
        $this->assertCount(0, $ticket->getComments());
        $this->assertNull($comment->getTicket());
    }

    public function testTicketOwnerAndAssignee()
    {
        $owner = new User();
        $assignee = new User();
        $ticket = new Ticket();

        $ticket->setOwner($owner);
        $ticket->setAssignee($assignee);

        $this->assertSame($owner, $ticket->getOwner());
        $this->assertSame($assignee, $ticket->getAssignee());
    }

    public function testTicketPriorityEnum()
    {
        $ticket = new Ticket();
        $ticket->setPriority(TicketPriority::LOW);
        $this->assertSame(TicketPriority::LOW, $ticket->getPriority());

        $ticket->setPriority(TicketPriority::NORMAL);
        $this->assertSame(TicketPriority::NORMAL, $ticket->getPriority());

        $ticket->setPriority(TicketPriority::HIGH);
        $this->assertSame(TicketPriority::HIGH, $ticket->getPriority());

        // Tester une valeur non autorisée si ta logique métier le prévoit (déclencher exception)
        // $this->expectException(\InvalidArgumentException::class);
        // $ticket->setPriority('fake');
    }

    public function testTicketStatusEnum()
    {
        $ticket = new Ticket();
        $ticket->setStatus(TicketStatus::PENDING);
        $this->assertSame(TicketStatus::PENDING, $ticket->getStatus());

        $ticket->setStatus(TicketStatus::WAITING);
        $this->assertSame(TicketStatus::WAITING, $ticket->getStatus());

        $ticket->setStatus(TicketStatus::IN_PROGRESS);
        $this->assertSame(TicketStatus::IN_PROGRESS, $ticket->getStatus());

        $ticket->setStatus(TicketStatus::DONE);
        $this->assertSame(TicketStatus::DONE, $ticket->getStatus());

        // Tester une valeur non autorisée si ta logique métier le prévoit (déclencher exception)
        // $this->expectException(\InvalidArgumentException::class);
        // $ticket->setStatus('closed');
    }
    public function testCreateTicket(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        // Créer un user pour le test
        $user = new User();
        $user->setName('John Doe');
        $user->setEmail('ticket@api.com');
        $user->setPassword('hash');
        $em->persist($user);
        $em->flush();

        $client->loginUser($user);

        $client->request('POST', '/api/tickets', [], [], ['CONTENT_TYPE' => 'application/ld+json'], json_encode([
            'title' => 'Nouveau ticket',
            'description' => 'Détails du ticket',
            'priority' => 'normale',
            'owner' => '/api/users/' . $user->getId()
        ]));

        $this->assertResponseStatusCodeSame(201);
        $response = $client->getResponse();
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Ticket created', $response->getContent());
    }
    public function testCreateTicketWithoutAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/tickets', [], [], ['CONTENT_TYPE' => 'application/ld+json'], json_encode([
            'title' => 'Nouveau ticket',
            'description' => 'Détails du ticket',
            'priority' => 'normale'
        ]));

        $this->assertResponseStatusCodeSame(401);
        $response = $client->getResponse();
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('JWT Token not found', $response->getContent());
    }

    public function testCreateTicketWithMissingFields(): void
    {
        // Ajoute l’utilisateur + loginUser
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = new User();
        $user->setName('Test Name');
        $user->setEmail('user@test.com');
        $user->setPassword('hash');
        $em->persist($user);
        $em->flush();

        $client->loginUser($user);

        $client->request('POST', '/api/tickets', [], [], ['CONTENT_TYPE' => 'application/ld+json'], json_encode([
            // title manquant pour provoquer une erreur
            'description' => 'Détails du ticket',
            'priority' => 'normale',
            'owner' => '/api/users/' . $user->getId()
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = $client->getResponse();
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Missing fields', $response->getContent());
    }

    public function testCreateTicketWithValidationErrors(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = new User();
        $user->setName('Test');
        $user->setEmail('test@validation.com');
        $user->setPassword('hash');
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);

        $client->request('POST', '/api/tickets', [], [], ['CONTENT_TYPE' => 'application/ld+json'], json_encode([
            'title' => str_repeat('a', 256), // Titre trop long
            'description' => 'Détails du ticket',
            'priority' => 'normale',
            'owner' => '/api/users/' . $user->getId()
        ]));

        $this->assertResponseStatusCodeSame(422);
        $response = $client->getResponse();
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Validation failed', $response->getContent());
    }

    public function testTicketCreatedAtIsImmutable(): void
    {
        $ticket = new Ticket();
        $createdAt = $ticket->getCreatedAt();

        // Essayer de modifier la date de création
        $this->expectException(\BadMethodCallException::class);
        $ticket->setCreatedAt(new \DateTimeImmutable('2020-01-01'));

        // Vérifier que la date de création n'a pas changé
        $this->assertSame($createdAt, $ticket->getCreatedAt());
    }

    public function testTicketAssignedAtFirstAndLast(): void
    {
        $ticket = new Ticket();
        $now = new \DateTimeImmutable();
        $later = $now->modify('+1 hour');

        $ticket->setAssignedAtFirst($now);
        $ticket->setAssignedAtLast($later);

        $this->assertSame($now, $ticket->getAssignedAtFirst());
        $this->assertSame($later, $ticket->getAssignedAtLast());
    }

    public function testTicketOwnerAndAssigneeRelation(): void
    {
        $owner = new User();
        $assignee = new User();
        $ticket = new Ticket();

        $ticket->setOwner($owner);
        $ticket->setAssignee($assignee);

        $this->assertSame($owner, $ticket->getOwner());
        $this->assertSame($assignee, $ticket->getAssignee());
    }

    public function testTicketCommentsRelation(): void
    {
        $ticket = new Ticket();
        $comment = new Comment();

        $ticket->addComment($comment);
        $this->assertCount(1, $ticket->getComments());
        $this->assertSame($ticket, $comment->getTicket());

        $ticket->removeComment($comment);
        $this->assertCount(0, $ticket->getComments());
        $this->assertNull($comment->getTicket());
    }

    public function testTicketToString(): void
    {
        $ticket = new Ticket();
        $ticket->setTitle('Test Ticket');
        $this->assertSame('Test Ticket', (string) $ticket);
    }

    public function testTicketStatusTransitions(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(TicketStatus::PENDING);

        // Transition to 'in_progress'
        $ticket->setStatus(TicketStatus::IN_PROGRESS);
        $this->assertSame(TicketStatus::IN_PROGRESS, $ticket->getStatus());

        // Transition to 'done'
        $ticket->setStatus(TicketStatus::DONE);
        $this->assertSame(TicketStatus::DONE, $ticket->getStatus());

        // Test invalid transition (if your logic allows it)
        // $this->expectException(\InvalidArgumentException::class);
        // $ticket->setStatus('closed');
    }

    public function testTicketPriorityTransitions(): void
    {
        $ticket = new Ticket();
        $ticket->setPriority(TicketPriority::LOW);

        // Transition to 'normale'
        $ticket->setPriority(TicketPriority::NORMAL);
        $this->assertSame(TicketPriority::NORMAL, $ticket->getPriority());

        // Transition to 'haute'
        $ticket->setPriority(TicketPriority::HIGH);
        $this->assertSame(TicketPriority::HIGH, $ticket->getPriority());

        // Test invalid transition (if your logic allows it)
        // $this->expectException(\InvalidArgumentException::class);
        // $ticket->setPriority('fake');
    }

    public function testTicketCreatedAtIsSetOnCreation(): void
    {
        $ticket = new Ticket();
        $createdAt = $ticket->getCreatedAt();

        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);

        // Si tu veux comparer la date du jour sans l'heure :
        $expected = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $actual = $createdAt->setTime(0, 0, 0);
        $this->assertEquals($expected, $actual);
    }

    public function testTicketToStringReturnsTitle(): void
    {
        $ticket = new Ticket();
        $ticket->setTitle('Test Ticket');
        $this->assertSame('Test Ticket', (string) $ticket);
    }

    public function testTicketStatusIsImmutable(): void
    {
        $ticket = new Ticket();
        $this->expectException(\TypeError::class);
        /*@phpstan-ignore-next-line*/
        $ticket->setStatus('closed'); // invalid type - passing string instead of enum
    }

    public function testTicketPriorityIsImmutable(): void
    {
        $ticket = new Ticket();
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        $ticket->setPriority('fake'); // invalid type
    }
}
