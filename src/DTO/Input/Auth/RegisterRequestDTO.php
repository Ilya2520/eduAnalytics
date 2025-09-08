<?php

declare(strict_types=1);

namespace App\DTO\Input\Auth;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(title: 'RegisterRequestDTO', description: 'Registration payload')]
class RegisterRequestDTO
{
    #[OA\Property]
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[OA\Property]
    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password;

    #[OA\Property]
    #[Assert\NotBlank]
    public string $firstName;

    #[OA\Property]
    #[Assert\NotBlank]
    public string $lastName;

    #[OA\Property(enum: ['admissions', 'marketing', 'administration'])]
    #[Assert\Choice(choices: ['admissions', 'marketing', 'administration'])]
    public string $department;
} 