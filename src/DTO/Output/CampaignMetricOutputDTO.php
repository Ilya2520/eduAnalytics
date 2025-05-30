<?php

declare(strict_types=1);

namespace App\DTO\Output;

use App\Entity\CampaignMetric;
use OpenApi\Attributes as OA;

#[OA\Schema(title: 'CampaignMetricOutput', description: 'Detailed information about a campaign metric including calculated values.')]
class CampaignMetricOutputDTO
{
    #[OA\Property(description: 'ID of the metric.', example: 1)]
    public int $id;

    #[OA\Property(description: 'ID of the associated campaign.', example: 123)]
    public int $campaignId; // Предполагается, что это поле уже было добавлено

    #[OA\Property(description: 'Date of the metric record.', type: 'string', format: 'date-time', example: '2024-05-25T10:00:00+00:00')]
    public \DateTimeInterface $recordDate;

    #[OA\Property(description: 'Number of impressions (установлено в 0).', example: 0)]
    public int $impressions;

    #[OA\Property(description: 'Number of clicks (установлено в 0).', example: 0)]
    public int $clicks;

    // #[OA\Property(description: "Applications generated (устаревшее, установлено в 0).", example: 0)]
    // public int $applicationsGenerated; // Если поле остается

    // Первичные метрики
    #[OA\Property(description: 'Количество поступивших (КП).', example: 5)]
    public int $enrolledStudents;

    #[OA\Property(description: 'Общее количество заявок (КЗ).', example: 50)]
    public int $totalApplications;

    #[OA\Property(description: 'Бюджет кампании.', type: 'number', format: 'float', example: 10000.00)]
    public float $campaignBudget;

    #[OA\Property(description: 'Затраты на рекламу (ЗР).', type: 'number', format: 'float', example: 2000.00)]
    public float $advertisingCosts;

    #[OA\Property(description: 'Общий доход (ОД).', type: 'number', format: 'float', example: 5000.00)]
    public float $totalRevenue;

    // Аналитические (рассчитанные) метрики
    #[OA\Property(description: 'Стоимость привлечения заявки (CPL = ЗР / КЗ).', type: 'number', format: 'float', nullable: true, example: 40.00)]
    public ?float $costPerApplication;

    #[OA\Property(description: 'Коэффициент конверсии в поступивших (CR = (КП / КЗ) * 100).', type: 'number', format: 'float', nullable: true, example: 10.0)]
    public ?float $conversionRate;

    #[OA\Property(description: 'Стоимость привлечения поступившего (CPA = ЗР / КП).', type: 'number', format: 'float', nullable: true, example: 400.00)]
    public ?float $costPerEnrolledStudent; // Новое

    #[OA\Property(description: 'Коэффициент возврата инвестиций (ROI = ((ОД - ЗР) / ЗР) * 100).', type: 'number', format: 'float', nullable: true, example: 150.0)]
    public ?float $roi;

    public static function createFromEntity(CampaignMetric $metric): self
    {
        $dto = new self();
        $dto->id = $metric->getId();
        $dto->campaignId = $metric->getCampaign()->getId(); // Убедитесь, что это не вызывает ошибку (кампания всегда есть)
        $dto->recordDate = $metric->getRecordDate();

        $dto->impressions = $metric->getImpressions();
        $dto->clicks = $metric->getClicks();
        // $dto->applicationsGenerated = $metric->getApplicationsGenerated(); // Если поле остается

        $dto->enrolledStudents = $metric->getEnrolledStudents();
        $dto->totalApplications = $metric->getTotalApplications();
        $dto->campaignBudget = $metric->getCampaignBudget();
        $dto->advertisingCosts = $metric->getAdvertisingCosts();
        $dto->totalRevenue = $metric->getTotalRevenue();

        $dto->costPerApplication = $metric->getCostPerApplication();
        $dto->conversionRate = $metric->getConversionRate();
        $dto->costPerEnrolledStudent = $metric->getCostPerEnrolledStudent();
        $dto->roi = $metric->getRoi();

        return $dto;
    }
}
