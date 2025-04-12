<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MarketingCampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarketingCampaignRepository::class)]
#[ORM\Table(name: 'marketing_campaigns')]
class MarketingCampaign
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $endDate;

    #[ORM\Column(type: 'float')]
    private float $budget;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status; // 'planned', 'active', 'completed', 'cancelled'

    #[ORM\Column(type: 'string', length: 50)]
    private string $channel; // 'email', 'social', 'ads', 'events'

    #[ORM\ManyToMany(targetEntity: Applicant::class, inversedBy: 'marketingCampaigns')]
    #[ORM\JoinTable(name: 'marketing_campaign_applicants')]
    private Collection $applicants;

    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: CampaignMetric::class)]
    private Collection $metrics;

    public function __construct()
    {
        $this->applicants = new ArrayCollection();
        $this->metrics = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getBudget(): float
    {
        return $this->budget;
    }

    public function setBudget(float $budget): void
    {
        $this->budget = $budget;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): void
    {
        $this->channel = $channel;
    }

    public function getApplicants(): Collection
    {
        return $this->applicants;
    }

    public function setApplicants(Collection $applicants): void
    {
        $this->applicants = $applicants;
    }

    public function getMetrics(): Collection
    {
        return $this->metrics;
    }

    public function setMetrics(Collection $metrics): void
    {
        $this->metrics = $metrics;
    }
}
