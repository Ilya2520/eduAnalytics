<?php

declare(strict_types=1);

namespace App\DTO\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

class CampaignMetricUpdateDTO
{
    #[Assert\Type(\DateTimeInterface::class, message: 'Record date must be a valid date/time if provided.')]
    #[OA\Property(description: 'Date of the metric record', type: 'string', format: 'date-time', nullable: true, example: '2024-05-26T12:00:00Z')]
    public ?\DateTimeInterface $recordDate = null;

    // Первичные метрики (опционально для обновления)
    #[Assert\PositiveOrZero(message: 'Enrolled students must be a non-negative integer if provided.')]
    #[OA\Property(description: 'Количество поступивших (КП)', type: 'integer', nullable: true, example: 6)]
    public ?int $enrolledStudents = null;

    #[Assert\PositiveOrZero(message: 'Total applications must be a non-negative integer if provided.')]
    #[OA\Property(description: 'Общее количество заявок (КЗ)', type: 'integer', nullable: true, example: 55)]
    public ?int $totalApplications = null;

    #[Assert\PositiveOrZero(message: 'Campaign budget must be a non-negative number if provided.')]
    #[OA\Property(description: 'Бюджет кампании', type: 'number', format: 'float', nullable: true, example: 10500.00)]
    public ?float $campaignBudget = null;

    #[Assert\PositiveOrZero(message: 'Advertising costs must be a non-negative number if provided.')]
    #[OA\Property(description: 'Затраты на рекламу (ЗР)', type: 'number', format: 'float', nullable: true, example: 2100.00)]
    public ?float $advertisingCosts = null;

    #[Assert\PositiveOrZero(message: 'Total revenue must be a non-negative number if provided.')]
    #[OA\Property(description: 'Общий доход (ОД)', type: 'number', format: 'float', nullable: true, example: 5500.00)]
    public ?float $totalRevenue = null;
}
