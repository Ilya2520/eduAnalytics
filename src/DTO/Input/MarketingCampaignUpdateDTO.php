<?php

declare(strict_types=1);

namespace App\DTO\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MarketingCampaignUpdateDTO
{
    #[Assert\Length(min: 3, max: 255, minMessage: 'Name must be at least 3 characters long.', maxMessage: 'Name cannot be longer than 255 characters.')]
    #[OA\Property(description: 'Name of the marketing campaign', nullable: true, example: 'Holiday Promotion (Updated)')]
    public ?string $name = null;

    #[Assert\Length(max: 10000, maxMessage: 'Description cannot be longer than 10000 characters.')]
    #[OA\Property(description: 'Optional description of the campaign', nullable: true, example: 'Updated holiday offers.')]
    public ?string $description = null;

    #[Assert\Type(\DateTimeInterface::class, message: 'Start date must be a valid date if provided.')]
    #[OA\Property(description: 'Start date of the campaign (e.g., YYYY-MM-DD)', type: 'string', format: 'date', nullable: true, example: '2025-11-05')]
    public ?\DateTimeInterface $startDate = null;

    #[Assert\Type(\DateTimeInterface::class, message: 'End date must be a valid date if provided.')]
    #[OA\Property(description: 'End date of the campaign (e.g., YYYY-MM-DD)', type: 'string', format: 'date', nullable: true, example: '2026-01-05')]
    public ?\DateTimeInterface $endDate = null;

    #[Assert\Positive(message: 'Budget must be a positive number if provided.')]
    #[OA\Property(description: 'Allocated budget for the campaign', type: 'number', format: 'float', nullable: true, example: 27000.00)]
    public ?float $budget = null;

    #[Assert\Choice(choices: ['planned', 'active', 'completed', 'cancelled'], message: 'Invalid status. Must be one of: planned, active, completed, cancelled.')]
    #[OA\Property(description: 'Status of the campaign', enum: ['planned', 'active', 'completed', 'cancelled'], nullable: true, example: 'active')]
    public ?string $status = null;

    #[Assert\Choice(choices: ['email', 'social', 'ads', 'events'], message: 'Invalid channel. Must be one of: email, social, ads, events.')]
    #[OA\Property(description: 'Primary channel for the campaign', enum: ['email', 'social', 'ads', 'events'], nullable: true, example: 'social')]
    public ?string $channel = null;

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        // This callback only validates if both dates are provided in the DTO.
        // For comprehensive validation against existing entity values,
        // it's better to do it in the service layer after fetching the entity.
        if ($this->startDate !== null && $this->endDate !== null) {
            if ($this->startDate > $this->endDate) {
                $context->buildViolation('End date must be on or after the start date when both are provided.')
                    ->atPath('endDate')
                    ->addViolation();
            }
        }
    }
}
