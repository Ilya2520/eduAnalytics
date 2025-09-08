<?php

declare(strict_types=1);

namespace App\DTO\Output\Auth;

class AuthResponseDTO
{
    public function __construct(
        public string $token,
        public UserOutputDTO $user
    ) {
    }
} 