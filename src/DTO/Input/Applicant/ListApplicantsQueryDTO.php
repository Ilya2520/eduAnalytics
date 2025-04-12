<?php

declare(strict_types=1);

namespace App\DTO\Input\Applicant;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'ListApplicantsQueryDTO',
    description: 'Query parameters for listing applicants'
)]
class ListApplicantsQueryDTO
{
    #[OA\Property(description: 'Page number', default: 1, example: 1)]
    #[Assert\Positive]
    public int $page = 1;

    #[OA\Property(description: 'Items per page', example: 10, default: 10)]
    #[Assert\Range(min: 1, max: 100)]
    public int $limit = 10;

    #[OA\Property(description: 'Search by name or email', example: 'john')]
    public ?string $search = null;

    #[OA\Property(
        description: 'Sort field',
        enum: ['id', 'firstName', 'lastName', 'email', 'createdAt'],
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
