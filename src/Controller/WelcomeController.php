<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class WelcomeController extends AbstractController
{
    #[Route('/welcome', name: 'app_welcome', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Добро пожаловать в Symfony приложение!',
            'status' => 'success'
        ]);
    }
}
