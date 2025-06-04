<?php
namespace App\Tests\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketPriority;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TicketWorkflowControllerTest extends WebTestCase
{
    private function createUser(): User
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = new User();
        $user->setEmail(uniqid().'@test.com');
        $user->setPassword('hash');
        $user->setName('User');
        $em->persist($user);
        $em->flush();
        return $user;
    }

    private function createTicket(User $owner): Ticket
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $ticket = new Ticket();
        $ticket->setTitle('test');
        $ticket->setDescription('desc');
        $ticket->setPriority(TicketPriority::NORMAL);
        $ticket->setOwner($owner);
        $em->persist($ticket);
        $em->flush();
        return $ticket;
    }

    public function testAssignTicket(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $assignee = $this->createUser();
        $ticket = $this->createTicket($owner);

        $client->loginUser($owner);
        $client->request('POST', '/api/tickets/'.$ticket->getId().'/assign', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['assignee_id' => $assignee->getId()]));
        $this->assertResponseIsSuccessful();
    }

    public function testUnassignTicket(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $assignee = $this->createUser();
        $ticket = $this->createTicket($owner);

        $ticket->setAssignee($assignee);
        static::getContainer()->get('doctrine')->getManager()->flush();

        $client->loginUser($owner);
        $client->request('POST', '/api/tickets/'.$ticket->getId().'/unassign');
        $this->assertResponseIsSuccessful();

        $refreshed = static::getContainer()->get('doctrine')->getRepository(Ticket::class)->find($ticket->getId());
        $this->assertNull($refreshed->getAssignee());
    }

    public function testUnassignTicketForbiddenForNonOwner(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $other = $this->createUser();
        $ticket = $this->createTicket($owner);

        $client->loginUser($other);
        $client->request('POST', '/api/tickets/'.$ticket->getId().'/unassign');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testStartTicket(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $assignee = $this->createUser();
        $ticket = $this->createTicket($owner);
        $ticket->setAssignee($assignee);
        static::getContainer()->get('doctrine')->getManager()->flush();

        $client->loginUser($assignee);
        $client->request('POST', '/api/tickets/'.$ticket->getId().'/start');

        $this->assertResponseIsSuccessful();

        $refreshed = static::getContainer()->get('doctrine')->getRepository(Ticket::class)->find($ticket->getId());
        $this->assertSame('in_progress', $refreshed->getStatus()->value);
    }

    public function testStartTicketForbiddenForNonAssignee(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $assignee = $this->createUser();
        $other = $this->createUser();
        $ticket = $this->createTicket($owner);
        $ticket->setAssignee($assignee);
        static::getContainer()->get('doctrine')->getManager()->flush();

        $client->loginUser($other);
        $client->request('POST', '/api/tickets/'.$ticket->getId().'/start');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCloseTicket(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $assignee = $this->createUser();
        $ticket = $this->createTicket($owner);
        $ticket->setAssignee($assignee);
        static::getContainer()->get('doctrine')->getManager()->flush();

        $client->loginUser($assignee);
        $client->request('POST', '/api/tickets/'.$ticket->getId().'/close');

        $this->assertResponseIsSuccessful();

        $refreshed = static::getContainer()->get('doctrine')->getRepository(Ticket::class)->find($ticket->getId());
        $this->assertSame('done', $refreshed->getStatus()->value);
    }

    public function testCloseTicketForbiddenForNonAssignee(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $assignee = $this->createUser();
        $other = $this->createUser();
        $ticket = $this->createTicket($owner);
        $ticket->setAssignee($assignee);
        static::getContainer()->get('doctrine')->getManager()->flush();

        $client->loginUser($other);
        $client->request('POST', '/api/tickets/'.$ticket->getId().'/close');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testMyTickets(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $other = $this->createUser();

        $this->createTicket($owner);
        $this->createTicket($owner);
        $this->createTicket($other);

        $client->loginUser($owner);
        $client->request('GET', '/api/my-tickets');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testAssignedTickets(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $assignee = $this->createUser();

        $t1 = $this->createTicket($owner);
        $t1->setAssignee($assignee);
        $t2 = $this->createTicket($owner);
        $t2->setAssignee($assignee);
        $this->createTicket($owner);
        static::getContainer()->get('doctrine')->getManager()->flush();

        $client->loginUser($assignee);
        $client->request('GET', '/api/assigned-tickets');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testMe(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(401);

        $client->loginUser($user);
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $token = $jwtManager->create($user);
        $client->request('GET', '/api/me', [], [], ['HTTP_Authorization' => 'Bearer '.$token]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($user->getId(), $data['id']);
    }
}
