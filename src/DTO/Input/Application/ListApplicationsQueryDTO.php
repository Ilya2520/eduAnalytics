<?php

declare(strict_types=1);

namespace App\DTO\Input\Application;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'ListApplicationsQueryDTO',
    description: 'Query parameters for listing applications'
)]
class ListApplicationsQueryDTO
{
    #[OA\Property(description: 'Page number', default: 1, example: 1)]
    #[Assert\Positive]
    public int $page = 1;

    #[OA\Property(description: 'Items per page', default: 10, example: 10)]
    #[Assert\Range(min: 1, max: 100)]
    public int $limit = 10;

    #[OA\Property(
        description: 'Status filter',
        enum: ['draft', 'submitted', 'under_review', 'accepted', 'rejected']
    )]
    public ?string $status = null;

    #[OA\Property(description: 'Applicant ID filter')]
    #[Assert\Positive]
    public ?int $applicantId = null;

    #[OA\Property(description: 'Program ID filter')]
    #[Assert\Positive]
    public ?int $programId = null;

    #[OA\Property(
        description: 'Sort field',
        enum: ['id', 'status', 'createdAt', 'updatedAt'],
        example: 'createdAt'
    )]
    public ?string $sortBy = 'createdAt';

    #[OA\Property(
        description: 'Sort direction',
        enum: ['asc', 'desc'],
        example: 'desc'
    )]
    public ?string $sortDirection = 'desc';
}
