<?php

declare(strict_types=1);

namespace App\DTO\Input\Report;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(title: 'ListReportsQueryDTO', description: 'Query parameters for listing reports')]
class ListReportsQueryDTO
{
    #[OA\Property(description: 'Page number', default: 1, example: 1)]
    #[Assert\Positive]
    public int $page = 1;

    #[OA\Property(description: 'Items per page', example: 10, default: 10)]
    #[Assert\Range(min: 1, max: 100)]
    public int $limit = 10;

    #[OA\Property(description: 'Filter by type', example: 'campaign_metrics_summary', nullable: true)]
    public ?string $type = null;

    #[OA\Property(description: 'Filter by status', enum: ['pending', 'processing', 'completed', 'failed'], nullable: true)]
    public ?string $status = null;
} 