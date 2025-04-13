<?php

declare(strict_types=1);

namespace App\DTO\Output;

use App\Entity\MarketingCampaign;
use DateTimeInterface;

class MarketingCampaignOutputDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public DateTimeInterface $startDate,
        public DateTimeInterface $endDate,
        public float $budget,
        public string $status,
        public string $channel,
        public int $applicantsCount,
        public array $metricsSummary
    ) {
    }

    public static function createFromEntity(MarketingCampaign $campaign): self
    {
        return new self(
            $campaign->getId(),
            $campaign->getName(),
            $campaign->getDescription(),
            $campaign->getStartDate(),
            $campaign->getEndDate(),
            $campaign->getBudget(),
            $campaign->getStatus(),
            $campaign->getChannel(),
            $campaign->getApplicants()->count(),
            [
                'totalImpressions' => array_sum($campaign->getMetrics()->map(
                    fn ($m) => $m->getImpressions()
                )->toArray()),
                'totalClicks' => array_sum($campaign->getMetrics()->map(
                    fn ($m) => $m->getClicks()
                )->toArray()),
                'totalApplications' => array_sum($campaign->getMetrics()->map(
                    fn ($m) => $m->getApplicationsGenerated()
                )->toArray()),
            ]
        );
    }
}
