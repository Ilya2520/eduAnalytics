<?php

declare(strict_types=1);

namespace App\DTO\Output;

use OpenApi\Attributes as OA;

#[OA\Schema(title: 'PaginatedResponse')]
class PaginatedResponseDTO
{
    /**
     * @var array<mixed>
     */
    #[OA\Property(description: 'Array of items for the current page.')]
    public array $items;

    #[OA\Property(description: 'Total number of items across all pages.', example: 100)]
    public int $total;

    #[OA\Property(description: 'Current page number.', example: 1)]
    public int $page;

    #[OA\Property(description: 'Number of items per page.', example: 10)]
    public int $limit;

    #[OA\Property(description: 'Total number of pages.', example: 10)]
    public int $pages;
}
