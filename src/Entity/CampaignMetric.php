<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CampaignMetricRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampaignMetricRepository::class)]
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

    // Основные метрики, которые теперь будут устанавливаться иначе
    #[ORM\Column(type: 'integer')]
    private int $impressions = 0; // Будет устанавливаться в 0

    #[ORM\Column(type: 'integer')]
    private int $clicks = 0; // Будет устанавливаться в 0

    // Это поле, вероятно, заменено totalApplications или будет устанавливаться в 0
    #[ORM\Column(type: 'integer')]
    private int $applicationsGenerated = 0;

    // Первичные метрики (пользовательский ввод)
    #[ORM\Column(type: 'integer')]
    private int $enrolledStudents; // Количество поступивших (КП)

    #[ORM\Column(type: 'integer')]
    private int $totalApplications; // Общее количество заявок (КЗ)

    #[ORM\Column(type: 'float')]
    private float $campaignBudget; // Бюджет кампании

    #[ORM\Column(type: 'float')]
    private float $advertisingCosts; // Затраты на рекламу (ЗР)

    #[ORM\Column(type: 'float')]
    private float $totalRevenue; // Общий доход (ОД)

    // Аналитические метрики (рассчитываются)
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $costPerApplication = null; // CPL

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $conversionRate = null; // CR

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $costPerEnrolledStudent = null; // CPA - Новое поле

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $roi = null; // ROI

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampaign(): MarketingCampaign
    {
        return $this->campaign;
    }

    public function setCampaign(MarketingCampaign $campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getRecordDate(): \DateTimeInterface
    {
        return $this->recordDate;
    }

    public function setRecordDate(\DateTimeInterface $recordDate): self
    {
        $this->recordDate = $recordDate;

        return $this;
    }

    // Impressions, Clicks, applicationsGenerated - сеттеры могут принимать значение, но сервис будет передавать 0
    public function getImpressions(): int
    {
        return $this->impressions;
    }

    public function setImpressions(int $impressions): self
    {
        $this->impressions = $impressions;

        return $this;
    }

    public function getClicks(): int
    {
        return $this->clicks;
    }

    public function setClicks(int $clicks): self
    {
        $this->clicks = $clicks;

        return $this;
    }

    public function getApplicationsGenerated(): int
    {
        return $this->applicationsGenerated;
    }

    public function setApplicationsGenerated(int $applicationsGenerated): self
    {
        $this->applicationsGenerated = $applicationsGenerated;

        return $this;
    }


    // Геттеры и сеттеры для первичных метрик
    public function getEnrolledStudents(): int
    {
        return $this->enrolledStudents;
    }

    public function setEnrolledStudents(int $enrolledStudents): self
    {
        $this->enrolledStudents = $enrolledStudents;

        return $this;
    }

    public function getTotalApplications(): int
    {
        return $this->totalApplications;
    }

    public function setTotalApplications(int $totalApplications): self
    {
        $this->totalApplications = $totalApplications;

        return $this;
    }

    public function getCampaignBudget(): float
    {
        return $this->campaignBudget;
    }

    public function setCampaignBudget(float $campaignBudget): self
    {
        $this->campaignBudget = $campaignBudget;

        return $this;
    }

    public function getAdvertisingCosts(): float
    {
        return $this->advertisingCosts;
    }

    public function setAdvertisingCosts(float $advertisingCosts): self
    {
        $this->advertisingCosts = $advertisingCosts;

        return $this;
    }

    public function getTotalRevenue(): float
    {
        return $this->totalRevenue;
    }

    public function setTotalRevenue(float $totalRevenue): self
    {
        $this->totalRevenue = $totalRevenue;

        return $this;
    }

    // Геттеры и сеттеры для аналитических метрик (должны позволять null)
    public function getCostPerApplication(): ?float
    {
        return $this->costPerApplication;
    }

    public function setCostPerApplication(?float $costPerApplication): self
    {
        $this->costPerApplication = $costPerApplication;

        return $this;
    }

    public function getConversionRate(): ?float
    {
        return $this->conversionRate;
    }

    public function setConversionRate(?float $conversionRate): self
    {
        $this->conversionRate = $conversionRate;

        return $this;
    }

    public function getCostPerEnrolledStudent(): ?float
    {
        return $this->costPerEnrolledStudent;
    }

    public function setCostPerEnrolledStudent(?float $costPerEnrolledStudent): self
    {
        $this->costPerEnrolledStudent = $costPerEnrolledStudent;

        return $this;
    }

    public function getRoi(): ?float
    {
        return $this->roi;
    }

    public function setRoi(?float $roi): self
    {
        $this->roi = $roi;

        return $this;
    }
}
