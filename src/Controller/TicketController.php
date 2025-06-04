<?php
// src/Controller/TicketController.php

namespace App\Controller;

use App\Entity\Ticket;
use App\Enum\TicketPriority;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TicketController extends AbstractController
{
    #[Route('/api/tickets', name: 'ticket_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        Security $security
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'], $data['description'], $data['priority'])) {
            return $this->json(['error' => 'Missing fields'], 400);
        }

        $ticket = new Ticket();
        $ticket->setTitle($data['title']);
        $ticket->setDescription($data['description']);

        try {
            $ticket->setPriority(TicketPriority::from($data['priority']));
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid priority'], 400);
        }
        $ticket->setOwner($security->getUser());

        $errors = $validator->validate($ticket);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 422);
        }

        $em->persist($ticket);
        $em->flush();

        return $this->json(['message' => 'Ticket created'], 201);
    }
}
