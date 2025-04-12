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

    // Getters and setters...
}
