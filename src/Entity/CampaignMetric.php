<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CampaignMetricRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass:CampaignMetricRepository::class)]
#[ORM\Table(name: 'campaign_metrics')]
class CampaignMetric
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MarketingCampaign::class, inversedBy: 'metrics')]
    #[ORM\JoinColumn(nullable: false)]
    private MarketingCampaign $campaign;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $recordDate;

    #[ORM\Column(type: 'integer')]
    private int $impressions;

    #[ORM\Column(type: 'integer')]
    private int $clicks;

    #[ORM\Column(type: 'integer')]
    private int $applicationsGenerated;

    #[ORM\Column(type: 'float')]
    private float $costPerApplication;

    #[ORM\Column(type: 'float')]
    private float $conversionRate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getCampaign(): MarketingCampaign
    {
        return $this->campaign;
    }

    public function setCampaign(MarketingCampaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function getRecordDate(): \DateTimeInterface
    {
        return $this->recordDate;
    }

    public function setRecordDate(\DateTimeInterface $recordDate): void
    {
        $this->recordDate = $recordDate;
    }

    public function getImpressions(): int
    {
        return $this->impressions;
    }

    public function setImpressions(int $impressions): void
    {
        $this->impressions = $impressions;
    }

    public function getClicks(): int
    {
        return $this->clicks;
    }

    public function setClicks(int $clicks): void
    {
        $this->clicks = $clicks;
    }

    public function getApplicationsGenerated(): int
    {
        return $this->applicationsGenerated;
    }

    public function setApplicationsGenerated(int $applicationsGenerated): void
    {
        $this->applicationsGenerated = $applicationsGenerated;
    }

    public function getCostPerApplication(): float
    {
        return $this->costPerApplication;
    }

    public function setCostPerApplication(float $costPerApplication): void
    {
        $this->costPerApplication = $costPerApplication;
    }

    public function getConversionRate(): float
    {
        return $this->conversionRate;
    }

    public function setConversionRate(float $conversionRate): void
    {
        $this->conversionRate = $conversionRate;
    }
}
