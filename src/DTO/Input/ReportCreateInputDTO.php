<?php

declare(strict_types=1);

namespace App\DTO\Input;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'ReportCreateInput',
    description: 'Data required to create and queue a new report for generation.'
)]
class ReportCreateInputDTO
{
    #[Assert\NotBlank(message: 'Report name cannot be blank.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Report name must be at least 3 characters.', maxMessage: 'Report name cannot exceed 255 characters.')]
    #[OA\Property(description: 'A descriptive name for the report.', example: 'Q1 Marketing Performance Overview')]
    public string $name;

    #[Assert\NotBlank(message: 'Report type cannot be blank.')]
    #[Assert\Length(min: 3, max: 50, minMessage: 'Report type must be at least 3 characters.', maxMessage: 'Report type cannot exceed 50 characters.')]
    #[OA\Property(description: "Identifier for the type of report (e.g., 'campaign_metrics_summary', 'lead_conversion_detail').", example: 'campaign_metrics_summary')]
    public string $type;

    #[Assert\NotNull(message: 'Report parameters must be provided.')]
    #[Assert\Valid] // This will trigger validation on the nested ReportParametersInputDTO

    #[OA\Property(description: 'Detailed parameters for configuring the report generation.', ref: new Model(type: ReportParametersInputDTO::class))]
    public ReportParametersInputDTO $parameters;
}
