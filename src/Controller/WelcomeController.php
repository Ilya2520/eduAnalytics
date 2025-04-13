<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class WelcomeController extends AbstractController
{
    public function __construct(readonly LoggerInterface $logger)
    {
    }

    #[Route('/welcome', name: 'app_welcome', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $this->logger->info('Welcome index');

        return $this->json([
            'message' => 'Добро пожаловать в Symfony приложение!',
            'status' => 'success'
        ]);
    }
}
