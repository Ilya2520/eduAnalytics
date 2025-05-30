<?php

declare(strict_types=1);

namespace App\DTO\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

class MarketingCampaignCreateDTO
{
    #[Assert\NotBlank(message: 'Campaign name cannot be blank.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Name must be at least 3 characters long.', maxMessage: 'Name cannot be longer than 255 characters.')]
    #[OA\Property(description: 'Name of the marketing campaign', example: 'Holiday Promotion')]
    public string $name;

    #[Assert\Length(max: 10000, maxMessage: 'Description cannot be longer than 10000 characters.')] // Adjust max length as needed
    #[OA\Property(description: 'Optional description of the campaign', nullable: true, example: 'Special holiday offers for selected products.')]
    public ?string $description = null;

    #[Assert\NotBlank(message: 'Start date cannot be blank.')]
    #[Assert\Type(\DateTimeInterface::class, message: 'Start date must be a valid date.')]
    #[OA\Property(description: 'Start date of the campaign (e.g., YYYY-MM-DD)', type: 'string', format: 'date', example: '2025-11-01')]
    public \DateTimeInterface $startDate;

    #[Assert\NotBlank(message: 'End date cannot be blank.')]
    #[Assert\Type(\DateTimeInterface::class, message: 'End date must be a valid date.')]
    #[Assert\GreaterThanOrEqual(propertyPath: 'startDate', message: 'End date must be on or after the start date.')]
    #[OA\Property(description: 'End date of the campaign (e.g., YYYY-MM-DD)', type: 'string', format: 'date', example: '2025-12-31')]
    public \DateTimeInterface $endDate;

    #[Assert\NotBlank(message: 'Budget cannot be blank.')]
    #[Assert\Positive(message: 'Budget must be a positive number.')]
    #[OA\Property(description: 'Allocated budget for the campaign', type: 'number', format: 'float', example: 25000.00)]
    public float $budget;

    #[Assert\NotBlank(message: 'Status cannot be blank.')]
    #[Assert\Choice(choices: ['planned', 'active', 'completed', 'cancelled'], message: 'Invalid status. Must be one of: planned, active, completed, cancelled.')]
    #[OA\Property(description: 'Status of the campaign', enum: ['planned', 'active', 'completed', 'cancelled'], example: 'planned')]
    public string $status;

    #[Assert\NotBlank(message: 'Channel cannot be blank.')]
    #[Assert\Choice(choices: ['email', 'social', 'ads', 'events'], message: 'Invalid channel. Must be one of: email, social, ads, events.')]
    #[OA\Property(description: 'Primary channel for the campaign', enum: ['email', 'social', 'ads', 'events'], example: 'email')]
    public string $channel;
}
