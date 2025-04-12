<?php

declare(strict_types=1);

namespace App\DTO\Output\Applicant;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'ApplicantListOutputDTO',
    description: 'Paginated list of applicants'
)]
class ApplicantListOutputDTO
{
    #[OA\Property(
        description: 'Array of applicants',
        type: 'array',
        items: new OA\Items(ref: new Model(type: ApplicantOutputDTO::class))
    )]
    public array $items;

    #[OA\Property(description: 'Current page number', example: 1)]
    public int $page;

    #[OA\Property(description: 'Items per page', example: 10)]
    public int $limit;

    #[OA\Property(description: 'Total items count', example: 100)]
    public int $total;
}
