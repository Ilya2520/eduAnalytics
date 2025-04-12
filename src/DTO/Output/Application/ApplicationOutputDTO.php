<?php

declare(strict_types=1);

namespace App\DTO\Output\Application;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'ApplicationOutputDTO',
    description: 'DTO for application output'
)]
class ApplicationOutputDTO
{
    #[OA\Property(description: 'Application ID', example: 1)]
    public int $id;

    #[OA\Property(description: 'Applicant ID', example: 1)]
    public int $applicantId;

    #[OA\Property(description: 'Program ID', example: 1)]
    public int $programId;

    #[OA\Property(
        description: 'Application status',
        enum: ['draft', 'submitted', 'under_review', 'accepted', 'rejected'],
        example: 'submitted'
    )]
    public string $status;

    #[OA\Property(
        description: 'Creation date',
        example: '2023-01-01T00:00:00+00:00'
    )]
    public \DateTimeInterface $createdAt;

    #[OA\Property(
        description: 'Last update date',
        example: '2023-01-01T00:00:00+00:00'
    )]
    public \DateTimeInterface $updatedAt;
}
