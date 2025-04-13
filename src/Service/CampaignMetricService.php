<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Output\CampaignMetricOutputDTO;
use App\Entity\CampaignMetric;
use App\Entity\MarketingCampaign;
use App\Repository\CampaignMetricRepository;
use App\Repository\MarketingCampaignRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CampaignMetricService
{
    public function __construct(
        private readonly CampaignMetricRepository $repository,
        private readonly MarketingCampaignRepository $campaignRepository,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getMetricsByCampaign(
        int $campaignId,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): array {
        try {
            // 1. Проверяем существование кампании
            $campaign = $this->campaignRepository->find($campaignId);
            if (!$campaign) {
                throw new \InvalidArgumentException('Marketing campaign not found');
            }

            // 2. Получаем метрики
            $metrics = $this->repository->findByCampaignAndDateRange(
                $campaignId,
                $startDate,
                $endDate
            );

            // 3. Преобразуем в DTO
            return array_map(
                fn (CampaignMetric $metric) => CampaignMetricOutputDTO::createFromEntity($metric),
                $metrics
            );

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning($e->getMessage(), ['campaignId' => $campaignId]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch campaign metrics', [
                'campaignId' => $campaignId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Could not fetch campaign metrics. Please try again later.');
        }
    }


    public function createMetric(array $data): CampaignMetric
    {
        $this->validateMetricData($data);

        try {
            /** @var MarketingCampaign $campaign */
            $campaign = $this->campaignRepository->find($data['campaignId']);
            if (!$campaign) {
                throw new \InvalidArgumentException('Marketing campaign not found');
            }

            $metric = new CampaignMetric();
            $metric->setCampaign($campaign);
            $metric->setRecordDate(new \DateTime($data['recordDate']));
            $metric->setImpressions($data['impressions']);
            $metric->setClicks($data['clicks']);
            $metric->setApplicationsGenerated($data['applicationsGenerated']);
            $metric->setCostPerApplication($data['costPerApplication']);
            $metric->setConversionRate($data['conversionRate']);

            $this->em->persist($metric);
            $this->em->flush();

            return $metric;
        } catch (\Exception $e) {
            $this->logger->error('Error creating campaign metric', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Could not create campaign metric');
        }
    }

    public function updateMetric(int $id, array $data): CampaignMetric
    {
        $metric = $this->repository->find($id);
        if (!$metric) {
            throw new \RuntimeException('Campaign metric not found');
        }

        $this->validateMetricData($data, true);

        try {
            if (isset($data['recordDate'])) {
                $metric->setRecordDate(new \DateTime($data['recordDate']));
            }
            if (isset($data['impressions'])) {
                $metric->setImpressions($data['impressions']);
            }
            if (isset($data['clicks'])) {
                $metric->setClicks($data['clicks']);
            }
            if (isset($data['applicationsGenerated'])) {
                $metric->setApplicationsGenerated($data['applicationsGenerated']);
            }
            if (isset($data['costPerApplication'])) {
                $metric->setCostPerApplication($data['costPerApplication']);
            }
            if (isset($data['conversionRate'])) {
                $metric->setConversionRate($data['conversionRate']);
            }

            $this->em->flush();

            return $metric;
        } catch (\Exception $e) {
            $this->logger->error('Error updating campaign metric', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Could not update campaign metric');
        }
    }

    public function deleteMetric(int $id): void
    {
        $metric = $this->repository->find($id);
        if (!$metric) {
            throw new \RuntimeException('Campaign metric not found');
        }

        try {
            $this->em->remove($metric);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error('Error deleting campaign metric', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Could not delete campaign metric');
        }
    }

    private function validateMetricData(array $data, bool $isUpdate = false): void
    {
        $requiredFields = [
            'campaignId',
            'recordDate',
            'impressions',
            'clicks',
            'applicationsGenerated',
            'costPerApplication',
            'conversionRate'
        ];

        foreach ($requiredFields as $field) {
            if (!$isUpdate && !array_key_exists($field, $data)) {
                throw new \InvalidArgumentException("Field $field is required");
            }
        }

        if (isset($data['impressions']) && $data['impressions'] < 0) {
            throw new \InvalidArgumentException('Impressions must be positive');
        }

        if (isset($data['clicks']) && $data['clicks'] < 0) {
            throw new \InvalidArgumentException('Clicks must be positive');
        }

        if (isset($data['applicationsGenerated']) && $data['applicationsGenerated'] < 0) {
            throw new \InvalidArgumentException('Applications generated must be positive');
        }

        if (isset($data['costPerApplication']) && $data['costPerApplication'] < 0) {
            throw new \InvalidArgumentException('Cost per application must be positive');
        }

        if (isset($data['conversionRate']) && ($data['conversionRate'] < 0 || $data['conversionRate'] > 100)) {
            throw new \InvalidArgumentException('Conversion rate must be between 0 and 100');
        }
    }
}
