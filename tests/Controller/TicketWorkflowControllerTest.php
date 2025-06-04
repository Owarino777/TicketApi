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
}
