<?php

declare(strict_types=1);

namespace App\DTO\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'ReportParameterCampaignInput',
    description: 'Specifies a marketing campaign and its selected metrics for a report.'
)]
class ReportParameterCampaignInputDTO
{
    #[Assert\NotBlank(message: 'Campaign ID must be provided for each selected campaign.')]
    #[Assert\Positive(message: 'Campaign ID must be a positive integer.')]
    #[OA\Property(description: 'ID of the selected marketing campaign.', example: 123)]
    public int $campaignId;

    /**
     * @var string[]
     */
    #[Assert\NotNull(message: 'Selected metrics array cannot be null.')]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, minMessage: 'At least one metric must be selected for the campaign.')]
    #[Assert\All([
        new Assert\NotBlank(allowNull: false, message: 'Metric name cannot be blank.'),
        new Assert\Type('string', message: 'Each metric name must be a string.'),
        // Consider Assert\Choice here if you have a predefined list of valid metric field names
        // new Assert\Choice(choices: ["impressions", "clicks", "roi", "costPerApplication", ...])
    ])]
    #[OA\Property(
        description: "List of metric field names (e.g., 'enrolled_students', 'total_applications', 'roi') to include from this campaign.",
        type: 'array',
        items: new OA\Items(type: 'string'),
        example: ['enrolled_students', 'total_applications', 'campaign_budget', 'roi']
    )]
    public array $selectedMetrics;
}
