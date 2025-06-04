<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Annotation\AsController;

class UserController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    public function getCurrentUser(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json($user);
    }
}
