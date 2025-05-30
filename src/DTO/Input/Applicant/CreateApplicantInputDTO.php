<?php

declare(strict_types=1);

namespace App\DTO\Input\Applicant;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'CreateApplicantInputDTO',
    description: 'DTO for creating an applicant'
)]
class CreateApplicantInputDTO
{
    #[OA\Property(description: 'First name', example: 'John')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $firstName;

    #[OA\Property(description: 'Last name', example: 'Doe')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $lastName;

    #[OA\Property(description: 'Email address', example: 'john.doe@example.com')]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public string $email;

    #[OA\Property(description: 'Phone number', example: '+1234567890', nullable: true)]
    #[Assert\Length(max: 20)]
    public ?string $phone = null;

    #[OA\Property(description: 'Birth date', example: '1990-01-01', nullable: true)]
    #[Assert\Type('\DateTimeInterface')]
    public ?\DateTimeInterface $birthDate = null;
}
