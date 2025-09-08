<?php

declare(strict_types=1);

namespace App\DTO\Output\Auth;

use App\Entity\User;

class UserOutputDTO
{
    public function __construct(
        public int $id,
        public string $email,
        public array $roles,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $department = null,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            id: (int) $user->getId(),
            email: (string) $user->getEmail(),
            roles: $user->getRoles(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            department: $user->getDepartment(),
        );
    }
} 