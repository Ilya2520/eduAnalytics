<?php

declare(strict_types=1);

namespace App\DTO\Output;

use App\Entity\CampaignMetric;
use DateTimeInterface;

class CampaignMetricOutputDTO
{
    public function __construct(
        public int $id,
        public int $campaignId,
        public string $campaignName,
        public DateTimeInterface $recordDate,
        public int $impressions,
        public int $clicks,
        public int $applicationsGenerated,
        public float $costPerApplication,
        public float $conversionRate
    ) {
    }

    public static function createFromEntity(CampaignMetric $metric): self
    {
        return new self(
            $metric->getId(),
            $metric->getCampaign()->getId(),
            $metric->getCampaign()->getName(),
            $metric->getRecordDate(),
            $metric->getImpressions(),
            $metric->getClicks(),
            $metric->getApplicationsGenerated(),
            $metric->getCostPerApplication(),
            $metric->getConversionRate()
        );
    }
}
