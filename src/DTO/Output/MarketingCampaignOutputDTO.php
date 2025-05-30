<?php

declare(strict_types=1);

namespace App\DTO\Output;

use App\Entity\MarketingCampaign;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'MarketingCampaignOutput',
    description: 'Detailed information about a marketing campaign.'
)]
class MarketingCampaignOutputDTO
{
    #[OA\Property(description: 'The unique identifier of the marketing campaign.', example: 1)]
    public int $id;

    #[OA\Property(description: 'Name of the marketing campaign.', example: 'Summer Sale 2025')]
    public string $name;

    #[OA\Property(description: 'Detailed description of the campaign.', nullable: true, example: 'Annual summer sale event.')]
    public ?string $description;

    #[OA\Property(description: 'Start date of the campaign.', type: 'string', format: 'date-time', example: '2025-06-01T00:00:00Z')]
    public \DateTimeInterface $startDate;

    #[OA\Property(description: 'End date of the campaign.', type: 'string', format: 'date-time', example: '2025-06-30T23:59:59Z')]
    public \DateTimeInterface $endDate;

    #[OA\Property(description: 'Allocated budget for the campaign.', type: 'number', format: 'float', example: 12000.75)]
    public float $budget;

    #[OA\Property(description: 'Current status of the campaign.', enum: ['planned', 'active', 'completed', 'cancelled'], example: 'active')]
    public string $status;

    #[OA\Property(description: 'Primary channel for the campaign.', enum: ['email', 'social', 'ads', 'events'], example: 'social')]
    public string $channel;

    // Note: Collections like 'applicants' and 'metrics' are omitted for brevity in this DTO.
    // If needed, they could be arrays of IDs or nested DTOs.

    public static function createFromEntity(MarketingCampaign $campaign): self
    {
        $dto = new self();
        $dto->id = $campaign->getId();
        $dto->name = $campaign->getName();
        $dto->description = $campaign->getDescription();
        $dto->startDate = $campaign->getStartDate();
        $dto->endDate = $campaign->getEndDate();
        $dto->budget = $campaign->getBudget();
        $dto->status = $campaign->getStatus();
        $dto->channel = $campaign->getChannel();

        return $dto;
    }
}
