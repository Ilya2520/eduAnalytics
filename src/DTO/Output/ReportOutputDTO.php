<?php

declare(strict_types=1);

namespace App\DTO\Output;

use App\DTO\Input\ReportParameterCampaignInputDTO;
use App\Entity\Report;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(title: 'ReportOutput', description: 'Detailed information about a report.')]
class ReportOutputDTO
{
    #[OA\Property(description: 'ID of the report.', example: 1)]
    public int $id;

    #[OA\Property(description: 'Name of the report.', example: 'Q1 Marketing Summary')]
    public string $name;

    #[OA\Property(description: 'Type of the report.', example: 'campaign_metrics_summary')]
    public string $type;

    /**
     * The structure of 'parameters' can be complex.
     * It's stored as JSON in the DB and returned as an array/object.
     * We describe its expected structure for reports created with the new DTO.
     */
    #[OA\Property(
        description: 'Parameters used for generating the report. Structure includes selected campaigns, metrics, report fields, and date filters.',
        type: 'object',
        properties: [
            new OA\Property(
                property: 'selectedCampaigns',
                type: 'array',
                items: new OA\Items(ref: new Model(type: ReportParameterCampaignInputDTO::class)) // Correctly reference the DTO for campaign parameters
            ),
            new OA\Property(
                property: 'reportFields',
                type: 'array',
                items: new OA\Items(type: 'string'),
                example: ['Campaign Name', 'Clicks', 'ROI']
            ),
            new OA\Property(property: 'startDate', type: 'string', format: 'date', nullable: true, example: '2024-01-01'),
            new OA\Property(property: 'endDate', type: 'string', format: 'date', nullable: true, example: '2024-03-31')
        ],
        example: [
            'selectedCampaigns' => [
                ['campaignId' => 1, 'selectedMetrics' => ['impressions', 'roi']],
                ['campaignId' => 2, 'selectedMetrics' => ['clicks', 'conversions']]
            ],
            'reportFields' => ['campaignName', 'impressions', 'clicks', 'roi', 'conversions'],
            'startDate' => '2024-01-01',
            'endDate' => '2024-03-31'
        ]
    )]
    public array $parameters;

    #[OA\Property(description: 'Current status of the report.', enum: ['pending', 'processing', 'completed', 'failed'], example: 'completed')]
    public string $status;

    #[OA\Property(description: 'Path to the generated report file, if completed.', nullable: true, example: '/reports/q1_marketing.xlsx')]
    public ?string $filePath;

    #[OA\Property(description: 'Timestamp of when the report was created.', type: 'string', format: 'date-time')]
    public \DateTimeInterface $createdAt;

    #[OA\Property(description: 'Timestamp of when the report was completed or failed.', type: 'string', format: 'date-time', nullable: true)]
    public ?\DateTimeInterface $completedAt;

    #[OA\Property(description: 'ID of the user who requested the report.', example: 101)]
    public int $requestedById;

    #[OA\Property(description: 'Email of the user who requested the report.', example: 'user@example.com')]
    public string $requestedByEmail;


    public static function createFromEntity(Report $report): self
    {
        $dto = new self();
        $dto->id = $report->getId();
        $dto->name = $report->getName();
        $dto->type = $report->getType();
        // The 'parameters' from the entity is already an array (decoded JSON)
        // The structure of this array should match what ReportParametersInputDTO defines if created through the new API
        $dto->parameters = $report->getParameters();
        $dto->status = $report->getStatus();
        $dto->filePath = $report->getFilePath();
        $dto->createdAt = $report->getCreatedAt();
        $dto->completedAt = $report->getCompletedAt();
        $dto->requestedById = $report->getRequestedBy()->getId(); // Ensure User entity has getId()
        $dto->requestedByEmail = $report->getRequestedBy()->getEmail(); // Ensure User entity has getEmail()

        return $dto;
    }
}
