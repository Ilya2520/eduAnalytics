<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractApiController extends AbstractController
{
    public function __construct(
        protected readonly SerializerInterface $serializer
    ) {
    }

    protected function respondWithError(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->json([
            'error' => [
                'code' => $status,
                'message' => $message
            ]
        ], $status);
    }

    protected function respond(object $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($data, $status);
    }
}
