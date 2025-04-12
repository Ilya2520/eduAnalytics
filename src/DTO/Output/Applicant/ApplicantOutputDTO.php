<?php

declare(strict_types=1);

namespace App\DTO\Output\Applicant;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'ApplicantOutputDTO',
    description: 'DTO for applicant output'
)]
class ApplicantOutputDTO
{
    #[OA\Property(description: 'Applicant ID', example: 1)]
    public int $id;

    #[OA\Property(description: 'First name', example: 'John')]
    public string $firstName;

    #[OA\Property(description: 'Last name', example: 'Doe')]
    public string $lastName;

    #[OA\Property(description: 'Email address', example: 'john.doe@example.com')]
    public string $email;

    #[OA\Property(description: 'Phone number', example: '+1234567890', nullable: true)]
    public ?string $phone;

    #[OA\Property(description: 'Birth date', example: '1990-01-01', nullable: true)]
    public ?string $birthDate;

    #[OA\Property(description: 'Creation date', example: '2023-01-01T00:00:00+00:00')]
    public \DateTimeInterface $createdAt;
}
