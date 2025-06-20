<?php
namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Security\TicketVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TicketWorkflowController extends AbstractController
{
    #[Route('/api/tickets/{id}/assign', name: 'ticket_assign', methods: ['POST'])]
    public function assign(int $id, Request $request, EntityManagerInterface $em, Security $security): JsonResponse
    {
        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], 404);
        }

        $this->denyAccessUnlessGranted(TicketVoter::EDIT, $ticket);

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
        if (!isset($data['assignee_id'])) {
            return $this->json(['error' => 'Missing assignee_id'], 400);
        }

        /** @var User|null $user */
        $user = $em->getRepository(User::class)->find($data['assignee_id']);
        if (!$user) {
            return $this->json(['error' => 'Assignee not found'], 404);
        }

        $ticket->setAssignee($user);
        $em->flush();

        return $this->json(['message' => 'Ticket assigned']);
    }

    #[Route('/api/tickets/{id}/unassign', name: 'ticket_unassign', methods: ['POST'])]
    public function unassign(int $id, EntityManagerInterface $em, Security $security): JsonResponse
    {
        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], 404);
        }

        $this->denyAccessUnlessGranted(TicketVoter::EDIT, $ticket);

        $ticket->setAssignee(null);
        $em->flush();

        return $this->json(['message' => 'Ticket unassigned']);
    }

    #[Route('/api/tickets/{id}/start', name: 'ticket_start', methods: ['POST'])]
    public function start(int $id, EntityManagerInterface $em, Security $security): JsonResponse
    {
        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], 404);
        }

        if ($ticket->getAssignee() !== $security->getUser()) {
            return $this->json(['error' => 'Access denied'], 403);
        }
        if ($ticket->getStatus() !== TicketStatus::WAITING) {
            return $this->json(['error' => 'Invalid status transition'], 400);
        }

        $ticket->setStatus(TicketStatus::IN_PROGRESS);
        $em->flush();

        return $this->json(['message' => 'Ticket started']);
    }

    #[Route('/api/tickets/{id}/close', name: 'ticket_close', methods: ['POST'])]
    public function close(int $id, EntityManagerInterface $em, Security $security): JsonResponse
    {
        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], 404);
        }

        if ($ticket->getAssignee() !== $security->getUser()) {
            return $this->json(['error' => 'Access denied'], 403);
        }
        if ($ticket->getStatus() !== TicketStatus::IN_PROGRESS) {
            return $this->json(['error' => 'Invalid status transition'], 400);
        }

        $ticket->setStatus(TicketStatus::DONE);
        $em->flush();

        return $this->json(['message' => 'Ticket closed']);
    }

    #[Route('/api/my-tickets', name: 'my_tickets', methods: ['GET'])]
    public function myTickets(EntityManagerInterface $em, Security $security): JsonResponse
    {
        $tickets = $em->getRepository(Ticket::class)->findBy(['owner' => $security->getUser()]);
        $data = array_map(static function (Ticket $ticket) {
            return [
                'id' => $ticket->getId(),
                'title' => $ticket->getTitle(),
                'status' => $ticket->getStatus()->value,
            ];
        }, $tickets);

        return $this->json($data);
    }

    #[Route('/api/assigned-tickets', name: 'assigned_tickets', methods: ['GET'])]
    public function assignedTickets(EntityManagerInterface $em, Security $security): JsonResponse
    {
        $tickets = $em->getRepository(Ticket::class)->findBy(['assignee' => $security->getUser()]);
        $data = array_map(static function (Ticket $ticket) {
            return [
                'id' => $ticket->getId(),
                'title' => $ticket->getTitle(),
                'status' => $ticket->getStatus()->value,
            ];
        }, $tickets);

        return $this->json($data);
    }

    #[Route('/api/me', name: 'current_user', methods: ['GET'])]
    public function me(Security $security): JsonResponse
    {
        /** @var User $user */
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ]);
    }
}
