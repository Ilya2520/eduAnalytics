<?php

declare(strict_types=1);

namespace App\DTO\Input\Program;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(title: 'ListProgramsQueryDTO', description: 'Query parameters for listing programs')]
class ListProgramsQueryDTO
{
    #[OA\Property(description: 'Page number', default: 1, example: 1)]
    #[Assert\Positive]
    public int $page = 1;

    #[OA\Property(description: 'Items per page', example: 10, default: 10)]
    #[Assert\Range(min: 1, max: 100)]
    public int $limit = 10;

    #[OA\Property(description: 'Filter by active status', example: true, nullable: true)]
    public ?bool $isActive = null;

    #[OA\Property(description: 'Degree filter', enum: ['bachelor', 'master', 'phd'], nullable: true)]
    public ?string $degree = null;
} 