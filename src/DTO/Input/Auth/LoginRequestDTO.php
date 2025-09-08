<?php

declare(strict_types=1);

namespace App\DTO\Input\Auth;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(title: 'LoginRequestDTO', description: 'Credentials for authentication')]
class LoginRequestDTO
{
    #[OA\Property(description: 'User email', example: 'user@example.com')]
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[OA\Property(description: 'User password', example: 'StrongP@ssw0rd')]
    #[Assert\NotBlank]
    public string $password;
} 