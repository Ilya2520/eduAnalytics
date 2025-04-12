<?php

declare(strict_types=1);

namespace App\DTO\Input\Application;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'CreateApplicationInputDTO',
    description: 'DTO for creating an application'
)]
class CreateApplicationInputDTO
{
    #[OA\Property(description: 'Applicant ID', example: 1)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $applicantId;

    #[OA\Property(description: 'Program ID', example: 1)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $programId;

    #[OA\Property(
        description: 'Application documents',
        type: 'array',
        items: new OA\Items(type: 'string')
    )]
    public ?array $documents = null;

    #[OA\Property(description: 'Additional notes', example: 'Special requirements')]
    public ?string $notes = null;
}
