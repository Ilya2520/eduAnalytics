<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class TokenService
{
    public function __construct(private readonly JWTTokenManagerInterface $jwtManager)
    {
    }

    public function createTokenForUser(User $user): string
    {
        return $this->jwtManager->create($user);
    }

    public function buildAuthResponse(User $user): \App\DTO\Output\Auth\AuthResponseDTO
    {
        return new \App\DTO\Output\Auth\AuthResponseDTO(
            token: $this->createTokenForUser($user),
            user: \App\DTO\Output\Auth\UserOutputDTO::fromEntity($user)
        );
    }
} 