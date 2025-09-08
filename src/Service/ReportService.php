<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Input\ReportCreateInputDTO;
use App\DTO\Output\CampaignMetricOutputDTO;
use App\DTO\Output\ReportOutputDTO;
use App\Entity\Enum\ReportStatusEnum;
use App\Entity\Report;
use App\Entity\User;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class ReportService
{
    public function __construct(
        private readonly ReportRepository $repository,
        private readonly CampaignMetricService $metricService,
        private readonly MarketingCampaignService $marketingCampaignService,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly Security $security,
        private readonly RabbitMQProducerService $rabbitMQProducerService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $reportGenerationTopic,
        private readonly string $reportWebhookRoute,
        private readonly \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher,
        private readonly \App\Builder\Report\ReportParametersBuilder $reportParametersBuilder,
    ) {
    }

    public function getReportsList(
        int $page,
        int $limit,
        ?string $type = null,
        ?string $status = null
    ): array {
        try {
            /** @var Paginator<Report> $paginator */
            $paginator = $this->repository->findByFilters($page, $limit, $type, $status);

            $items = [];
            foreach ($paginator->getIterator() as $report) {
                $items[] = ReportOutputDTO::createFromEntity($report);
            }

            $totalItems = $paginator->count();
            $pages = ($limit > 0 && $totalItems > 0) ? (int)ceil($totalItems / $limit) : 1;

            return [
                'items' => $items,
                'total' => $totalItems,
                'page' => $page,
                'limit' => $limit,
                'pages' => $pages
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error fetching reports list', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new \RuntimeException('Could not fetch reports list. Please try again later.');
        }
    }

    public function getReportById(int $id): Report
    {
        $report = $this->repository->find($id);
        if (!$report) {
            $this->logger->warning('Report not found by ID.', ['reportId' => $id]);
            throw new EntityNotFoundException('Report not found with ID: ' . $id);
        }

        return $report;
    }

    public function createReport(ReportCreateInputDTO $dto): Report
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            $this->logger->error('User not authenticated or invalid user type for report creation.');
            throw new \RuntimeException('User not found or not authenticated. Cannot create report.');
        }

        try {
            $report = new Report();
            $report->setName($dto->name);
            $report->setType($dto->type);

            $parametersArray = $this->reportParametersBuilder->buildParameters($dto);

            $report->setParameters($parametersArray);
            $report->setRequestedBy($user);

            $this->em->persist($report);
            $this->em->flush();

            $this->eventDispatcher->dispatch(\App\Event\ReportCreatedEvent::fromEntity($report));

            $this->logger->info('Report created and event dispatched.', ['reportId' => $report->getId(), 'reportName' => $report->getName()]);

            return $report;
        } catch (\Exception $e) {
            $this->logger->error('Error creating report or dispatching task', [
                'reportName' => $dto->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Could not create report. An internal error occurred.');
        }
    }

    private function getMetricValue(int $campaignId, array $selectedMetrics): array
    {
        $metrics = $this->metricService->getMetricsByCampaign($campaignId);
        $campaign = $this->marketingCampaignService->getMarketingCampaignById($campaignId);
        $result = [];

        /** @var CampaignMetricOutputDTO $metric */
        foreach ($metrics as $metric) {
            $result = [
                'name' => $campaign->getName(),
                'channel' => $campaign->getChannel(),
                'start' => $campaign->getStartDate()->format('Y-m-d'),
                'end' => $campaign->getEndDate()->format('Y-m-d'),
                'id' => $metric->id,
                'campaign_id' => $metric->campaignId,
                'record_date' => $metric->recordDate->format('Y-m-d'),
            ];

            foreach ($selectedMetrics as $metricName) {
                switch ($metricName) {
                    case 'enrolled_students':
                        $result['enrolled_students'] = $metric->enrolledStudents;
                        break;
                    case 'total_applications':
                        $result['total_applications'] = $metric->totalApplications;
                        break;
                    case 'campaign_budget':
                        $result['campaign_budget'] = $metric->campaignBudget;
                        break;
                    case 'advertising_costs':
                        $result['advertising_costs'] = $metric->advertisingCosts;
                        break;
                    case 'total_revenue':
                        $result['total_revenue'] = $metric->totalRevenue;
                        break;
                    case 'cost_per_application':
                        $result['cost_per_application'] = $metric->costPerApplication;
                        break;
                    case 'cost_per_enrolled':
                        $result['cost_per_enrolled'] = $metric->costPerEnrolledStudent;
                        break;
                    case 'conversion_rate':
                        $result['conversion_rate'] = $metric->conversionRate;
                        break;
                    case 'roi':
                        $result['roi'] = $metric->roi;
                        break;
                }
            }
        }

        return $result;
    }

    public function updateReportCompletionStatus(
        int $reportId,
        string $status,
        ?string $filePath = null,
        ?string $errorMessage = null
    ): Report {
        $report = $this->getReportById($reportId);

        if (!in_array($status, [ReportStatusEnum::completed->value, ReportStatusEnum::cancelled->value])) {
            throw new \InvalidArgumentException("Invalid completion status provided: '{$status}'. Must be 'completed' or 'failed'.");
        }

        if (in_array($report->getStatus(), [ReportStatusEnum::completed->value, ReportStatusEnum::cancelled->value])) {
            $this->logger->warning('Attempted to update status of an already finalized report.', [
                'reportId' => $reportId,
                'currentStatus' => $report->getStatus(),
                'newStatus' => $status
            ]);

            return $report;
        }

        try {
            $report->setStatus($status);
            $report->setCompletedAt(new \DateTimeImmutable());

            if ($status === ReportStatusEnum::completed->value) {
                if (empty($filePath)) {
                    throw new \InvalidArgumentException('File path must be provided for completed reports.');
                }
                $report->setFilePath($filePath);
                $this->logger->info('Report marked as completed.', ['reportId' => $reportId, 'filePath' => $filePath]);
            } else {
                $report->setFilePath(null);
                $this->logger->error('Report marked as failed.', ['reportId' => $reportId, 'errorMessage' => $errorMessage]);
            }

            $this->em->flush();

            return $report;
        } catch (\Exception $e) {
            $this->logger->error('Error updating report completion status in database.', [
                'reportId' => $reportId, 'newStatus' => $status,
                'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Could not update report status due to a database error.', 0, $e);
        }
    }

    public function deleteReport(int $id): void
    {
        $report = $this->getReportById($id);

        try {
            $filePath = $report->getFilePath();
            if ($filePath && file_exists($filePath)) {
                if (!unlink($filePath)) {
                    $this->logger->warning('Could not delete report file from disk.', ['reportId' => $id, 'filePath' => $filePath]);
                }
            }

            $this->em->remove($report);
            $this->em->flush();
            $this->logger->info('Report deleted successfully.', ['reportId' => $id]);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting report entity or its file.', ['reportId' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new \RuntimeException('Could not delete report.', 0, $e);
        }
    }
}
