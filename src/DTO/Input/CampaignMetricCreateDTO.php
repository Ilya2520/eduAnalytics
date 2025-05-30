<?php

declare(strict_types=1);

namespace App\DTO\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

class CampaignMetricCreateDTO
{
    #[Assert\NotBlank(message: 'Campaign ID cannot be blank.')]
    #[Assert\Positive(message: 'Campaign ID must be a positive integer.')]
    #[OA\Property(description: 'ID of the associated marketing campaign', type: 'integer', example: 1)]
    public int $campaignId;

    #[Assert\NotBlank(message: 'Record date cannot be blank.')]
    #[Assert\Type(\DateTimeInterface::class, message: 'Record date must be a valid date/time.')]
    #[OA\Property(description: 'Date of the metric record', type: 'string', format: 'date-time', example: '2024-05-25T12:00:00Z')]
    public \DateTimeInterface $recordDate;

    // Первичные метрики (ввод пользователя)
    #[Assert\NotBlank(message: 'Number of enrolled students cannot be blank.')]
    #[Assert\PositiveOrZero(message: 'Enrolled students must be a non-negative integer.')]
    #[OA\Property(description: 'Количество поступивших (КП)', type: 'integer', example: 5)]
    public int $enrolledStudents;

    #[Assert\NotBlank(message: 'Total applications cannot be blank.')]
    #[Assert\PositiveOrZero(message: 'Total applications must be a non-negative integer.')]
    #[OA\Property(description: 'Общее количество заявок (КЗ)', type: 'integer', example: 50)]
    public int $totalApplications;

    #[Assert\NotBlank(message: 'Campaign budget cannot be blank.')]
    #[Assert\PositiveOrZero(message: 'Campaign budget must be a non-negative number.')]
    #[OA\Property(description: 'Бюджет кампании', type: 'number', format: 'float', example: 10000.00)]
    public float $campaignBudget; // Не участвует в формулах A.1-A.4, но является первичной метрикой

    #[Assert\NotBlank(message: 'Advertising costs cannot be blank.')]
    #[Assert\PositiveOrZero(message: 'Advertising costs must be a non-negative number.')] // Для ROI > 0 предпочтительно
    #[OA\Property(description: 'Затраты на рекламу (ЗР)', type: 'number', format: 'float', example: 2000.00)]
    public float $advertisingCosts;

    #[Assert\NotBlank(message: 'Total revenue cannot be blank.')]
    #[Assert\PositiveOrZero(message: 'Total revenue must be a non-negative number.')]
    #[OA\Property(description: 'Общий доход (ОД)', type: 'number', format: 'float', example: 5000.00)]
    public float $totalRevenue;
}
