<?php

declare(strict_types=1);

namespace App\DTO\Input;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'ReportParametersInput',
    description: 'Detailed parameters for generating a report, including campaign selections and desired output fields.'
)]
class ReportParametersInputDTO
{
    /**
     * @var ReportParameterCampaignInputDTO[]
     */
    #[Assert\NotNull(message: 'Selected campaigns array cannot be null.')]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, minMessage: 'At least one marketing campaign must be selected for the report.')]
    #[Assert\Valid] // This will validate each ReportParameterCampaignInputDTO object in the array
    #[OA\Property(
        description: 'Array detailing each selected marketing campaign and the metrics to pull from it.',
        type: 'array',
        items: new OA\Items(ref: new Model(type: ReportParameterCampaignInputDTO::class))
    )]
    public array $selectedCampaigns;

    /**
     * @var string[]
     */
    #[Assert\NotNull(message: 'Report fields array cannot be null.')]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, minMessage: 'At least one field/column must be specified for the report output.')]
    #[Assert\All([
        new Assert\NotBlank(allowNull: false, message: 'Report field name cannot be blank.'),
        new Assert\Type('string', message: 'Each report field name must be a string.')
    ])]
    #[OA\Property(
        description: "List of fields/columns to include in the final generated report (e.g., 'Campaign Name', 'Impressions', 'ROI').",
        type: 'array',
        items: new OA\Items(type: 'string'),
        example: ['Campaign Name', 'Start Date', 'Total Impressions', 'Overall ROI']
    )]
    public array $reportFields;

    #[OA\Property(description: 'Optional start date to filter metrics for the report (YYYY-MM-DD).', type: 'string', format: 'date', nullable: true, example: '2024-01-01')]
    #[Assert\Type(\DateTimeInterface::class, message: 'Start date must be a valid date if provided.')]
    public ?\DateTimeInterface $startDate = null;

    #[OA\Property(description: 'Optional end date to filter metrics for the report (YYYY-MM-DD).', type: 'string', format: 'date', nullable: true, example: '2024-03-31')]
    #[Assert\Type(\DateTimeInterface::class, message: 'End date must be a valid date if provided.')]
    #[Assert\Expression(
        'this.startDate === null or this.endDate === null or this.endDate >= this.startDate',
        message: 'End date must be on or after the start date if both are provided.'
    )]
    public ?\DateTimeInterface $endDate = null;

    // Add any other general report parameters here, e.g., aggregation settings
}
