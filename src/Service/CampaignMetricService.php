<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Input\CampaignMetricCreateDTO;
use App\DTO\Input\CampaignMetricUpdateDTO;
use App\DTO\Output\CampaignMetricOutputDTO;
use App\Entity\CampaignMetric;
use App\Entity\MarketingCampaign;
use App\Repository\CampaignMetricRepository;
use App\Repository\MarketingCampaignRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
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

    /**
     * @return CampaignMetricOutputDTO[]
     */
    public function getMetricsByCampaign(
        int $campaignId,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): array {
        try {
            $campaign = $this->campaignRepository->find($campaignId);
            if (!$campaign) {
                throw new \InvalidArgumentException('Marketing campaign not found with ID: ' . $campaignId);
            }

            $metricsEntities = $this->repository->findByCampaignAndDateRange(
                $campaignId,
                $startDate,
                $endDate
            );

            return array_map(
                fn (CampaignMetric $metric) => CampaignMetricOutputDTO::createFromEntity($metric),
                $metricsEntities
            );
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning($e->getMessage(), ['campaignId' => $campaignId, 'startDate' => $startDate?->format('Y-m-d'), 'endDate' => $endDate?->format('Y-m-d')]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch campaign metrics', [
                'campaignId' => $campaignId,
                'startDate' => $startDate?->format('Y-m-d'),
                'endDate' => $endDate?->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Could not fetch campaign metrics. Please try again later.');
        }
    }

    public function createMetric(CampaignMetricCreateDTO $dto): CampaignMetric
    {
        try {
            /** @var MarketingCampaign|null $campaign */
            $campaign = $this->campaignRepository->find($dto->campaignId);
            if (!$campaign) {
                throw new \InvalidArgumentException('Marketing campaign not found with ID: ' . $dto->campaignId);
            }

            $metric = new CampaignMetric();
            $metric->setCampaign($campaign);
            $metric->setRecordDate($dto->recordDate);

            // Установка полей в 0
            $metric->setImpressions(0);
            $metric->setClicks(0);
            // $metric->setApplicationsGenerated(0); // Если это поле остается

            // Установка первичных метрик из DTO
            $metric->setEnrolledStudents($dto->enrolledStudents);
            $metric->setTotalApplications($dto->totalApplications);
            $metric->setCampaignBudget($dto->campaignBudget);
            $metric->setAdvertisingCosts($dto->advertisingCosts);
            $metric->setTotalRevenue($dto->totalRevenue);

            // Расчет и установка аналитических метрик
            $this->calculateAndSetAnalyticalMetrics($metric);

            $this->em->persist($metric);
            $this->em->flush();

            $this->logger->info('Campaign metric created successfully.', ['metricId' => $metric->getId(), 'campaignId' => $campaign->getId()]);

            return $metric;
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Failed to create campaign metric (validation or lookup): ' . $e->getMessage(), ['dto_campaignId' => $dto->campaignId]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Error creating campaign metric', ['error' => $e->getMessage(), 'dto_campaignId' => $dto->campaignId, 'trace' => $e->getTraceAsString()]);
            throw new \RuntimeException('Could not create campaign metric due to an internal error.');
        }
    }

    public function updateMetric(int $id, CampaignMetricUpdateDTO $dto): CampaignMetric
    {
        $metric = $this->repository->find($id);
        if (!$metric) {
            throw new EntityNotFoundException('Campaign metric not found with ID: ' . $id);
        }

        try {
            $updated = false;
            $recalculateAnalytics = false;

            if ($dto->recordDate !== null && $metric->getRecordDate() != $dto->recordDate) {
                $metric->setRecordDate($dto->recordDate);
                $updated = true;
            }

            // Обновление первичных метрик
            if ($dto->enrolledStudents !== null && $metric->getEnrolledStudents() !== $dto->enrolledStudents) {
                $metric->setEnrolledStudents($dto->enrolledStudents);
                $updated = true;
                $recalculateAnalytics = true;
            }
            if ($dto->totalApplications !== null && $metric->getTotalApplications() !== $dto->totalApplications) {
                $metric->setTotalApplications($dto->totalApplications);
                $updated = true;
                $recalculateAnalytics = true;
            }
            if ($dto->campaignBudget !== null && $metric->getCampaignBudget() !== $dto->campaignBudget) {
                $metric->setCampaignBudget($dto->campaignBudget);
                $updated = true; // Бюджет сам по себе не влияет на формулы A.1-A.4
            }
            if ($dto->advertisingCosts !== null && $metric->getAdvertisingCosts() !== $dto->advertisingCosts) {
                $metric->setAdvertisingCosts($dto->advertisingCosts);
                $updated = true;
                $recalculateAnalytics = true;
            }
            if ($dto->totalRevenue !== null && $metric->getTotalRevenue() !== $dto->totalRevenue) {
                $metric->setTotalRevenue($dto->totalRevenue);
                $updated = true;
                $recalculateAnalytics = true;
            }

            // clicks, impressions, applicationsGenerated не обновляются из DTO, т.к. они теперь фиксированы или устарели
            // Если нужно принудительно сбросить их в 0 при любом обновлении:
            // $metric->setImpressions(0);
            // $metric->setClicks(0);

            if ($recalculateAnalytics) {
                $this->calculateAndSetAnalyticalMetrics($metric);
                $updated = true; // Гарантируем flush, если аналитика изменилась
            }

            if ($updated) {
                $this->em->flush();
                $this->logger->info('Campaign metric updated successfully.', ['metricId' => $id]);
            }

            return $metric;
        } catch (\Exception $e) {
            $this->logger->error('Error updating campaign metric', ['id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new \RuntimeException('Could not update campaign metric due to an internal error.');
        }
    }

    private function calculateAndSetAnalyticalMetrics(CampaignMetric $metric): void
    {
        $kp = $metric->getEnrolledStudents();      // Количество поступивших
        $kz = $metric->getTotalApplications();     // Количество заявок
        $zr = $metric->getAdvertisingCosts();      // Затраты на рекламу
        $od = $metric->getTotalRevenue();          // Общий доход

        // CPL (Стоимость привлечения заявки) = ЗР / КЗ
        $metric->setCostPerApplication(($kz > 0) ? ($zr / $kz) : null);

        // CR (Коэффициент конверсии в поступивших) = (КП / КЗ) * 100
        $metric->setConversionRate(($kz > 0) ? (($kp / $kz) * 100) : null);

        // CPA (Стоимость привлечения поступивших) = ЗР / КП
        $metric->setCostPerEnrolledStudent(($kp > 0) ? ($zr / $kp) : null);

        // ROI (Коэффициент возврата инвестиций) = ((ОД - ЗР) / ЗР) * 100
        $metric->setROi(($zr > 0) ? ((($od - $zr) / $zr) * 100) : null); // Изменено: null вместо 0 при ЗР <= 0
    }


    public function deleteMetric(int $id): void
    {
        $metric = $this->repository->find($id);
        if (!$metric) {
            throw new EntityNotFoundException('Campaign metric not found with ID: ' . $id);
        }

        try {
            $this->em->remove($metric);
            $this->em->flush();
            $this->logger->info('Campaign metric deleted successfully.', ['metricId' => $id]);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting campaign metric', ['id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new \RuntimeException('Could not delete campaign metric due to an internal error.');
        }
    }
}
