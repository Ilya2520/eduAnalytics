<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Input\Auth\LoginRequestDTO;
use App\DTO\Input\Auth\RegisterRequestDTO;
use App\Service\AuthService;
use App\Service\TokenService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Security')]
class AuthController extends AbstractController
{
    use \App\Presentation\Http\ApiResponseTrait;

    public function __construct(
        private readonly AuthService $authService,
        private readonly TokenService $tokenService,
    ) {
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login',
        summary: 'Login with email and password',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new OA\Model(type: LoginRequestDTO::class))
        ),
        responses: [
            new OA\Response(response: 200, description: 'Successful login'),
            new OA\Response(response: 401, description: 'Invalid credentials')
        ]
    )]
    public function login(#[MapRequestPayload] LoginRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->authService->validateLogin($dto);
            return $this->ok($this->tokenService->buildAuthResponse($user));
        } catch (\InvalidArgumentException $e) {
            return $this->error((string) $e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->error('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new OA\Model(type: RegisterRequestDTO::class))),
        responses: [
            new OA\Response(response: 201, description: 'User successfully registered'),
            new OA\Response(response: 400, description: 'Invalid input')
        ]
    )]
    public function register(#[MapRequestPayload] RegisterRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->authService->register($dto);
            return $this->created(new \App\DTO\Output\Auth\AuthResponseDTO(
                token: $this->tokenService->createTokenForUser($user),
                user: \App\DTO\Output\Auth\UserOutputDTO::fromEntity($user)
            ));
        } catch (\InvalidArgumentException $e) {
            return $this->error((string) $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
