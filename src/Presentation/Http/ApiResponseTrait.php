<?php

declare(strict_types=1);

namespace App\Presentation\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponseTrait
{
    protected function ok(mixed $data): JsonResponse
    {
        return new JsonResponse($data, Response::HTTP_OK);
    }

    protected function created(mixed $data): JsonResponse
    {
        return new JsonResponse($data, Response::HTTP_CREATED);
    }

    protected function accepted(mixed $data): JsonResponse
    {
        return new JsonResponse($data, Response::HTTP_ACCEPTED);
    }

    protected function error(string|array $error, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        $payload = is_array($error) ? ['error' => $error] : ['error' => ['message' => $error]];
        return new JsonResponse($payload, $status);
    }
} 