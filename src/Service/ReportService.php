<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Input\ReportCreateInputDTO; // New DTO for creation
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

// Assuming RabbitMQProducerService is correctly defined elsewhere
// use App\Service\Integration\RabbitMQProducerService;

class ReportService
{
    private const REPORT_GENERATION_TOPIC = 'report.generate';
    private const REPORT_WEBHOOK_ROUTE = 'webhook_report_completed'; // Route name

    public function __construct(
        private readonly ReportRepository $repository,
        private readonly CampaignMetricService $metricService,
        private readonly MarketingCampaignService $marketingCampaignService,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly Security $security,
        private readonly RabbitMQProducerService $rabbitMQProducerService, // Ensure this service is correctly injected
        private readonly UrlGeneratorInterface $urlGenerator,
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
            foreach ($paginator->getIterator() as $report) { // Iterate over Paginator's iterator
                $items[] = ReportOutputDTO::createFromEntity($report);
            }

            $totalItems = $paginator->count(); // Get total count from Paginator
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

    public function getReportById(int $id): Report // Return entity for internal use
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
            // This should ideally be caught by security layer or be an AuthenticationException
            $this->logger->error('User not authenticated or invalid user type for report creation.');
            throw new \RuntimeException('User not found or not authenticated. Cannot create report.');
        }

        try {
            $report = new Report();
            $report->setName($dto->name);
            $report->setType($dto->type);

            // Convert ReportParametersInputDTO to an array for storing in the JSON field
            // Symfony's serializer (used by MapRequestPayload) hydrates DTOs.
            // We need to ensure $dto->parameters is correctly converted to an array if it's an object.
            // If $dto->parameters is already hydrated as an object of ReportParametersInputDTO,
            // we might need to explicitly serialize it to an array.
            // However, if properties are public, json_decode(json_encode($dto->parameters), true) can work,
            // or a dedicated method in the DTO.
            // For simplicity, let's assume the DTO structure can be cast or easily converted.
            // A robust way: use Symfony Serializer to normalize the DTO to an array.
            // For now, a simple approach:
            $parametersArray = [
                'selectedCampaigns' => array_map(fn ($cdto) => [
                    'campaignId' => $cdto->campaignId,
                    'selectedMetrics' => $cdto->selectedMetrics,
                    'metricValues' => $this->getMetricValue($cdto->campaignId, $cdto->selectedMetrics)
                    ], $dto->parameters->selectedCampaigns),
                'reportFields' => $dto->parameters->reportFields,
                'startDate' => $dto->parameters->startDate ? $dto->parameters->startDate->format('Y-m-d') : null,
                'endDate' => $dto->parameters->endDate ? $dto->parameters->endDate->format('Y-m-d') : null,
            ];
            // Remove null date entries to keep parameters clean
            $parametersArray = array_filter($parametersArray, fn ($value) => $value !== null);


            $report->setParameters($parametersArray);
            $report->setRequestedBy($user);
            // Status and CreatedAt are set by Report entity constructor

            $this->em->persist($report);
            $this->em->flush(); // Flush to get Report ID

            $this->dispatchReportGenerationTask($report);

            $this->logger->info('Report created and task dispatched.', ['reportId' => $report->getId(), 'reportName' => $report->getName()]);

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
                // Основные поля кампании (всегда включаются)
                'name' => $campaign->getName(),
                'channel' => $campaign->getChannel(),
                'start' => $campaign->getStartDate()->format('Y-m-d'),
                'end' => $campaign->getEndDate()->format('Y-m-d'),
                'id' => $metric->id,
                'campaign_id' => $metric->campaignId,
                'record_date' => $metric->recordDate->format('Y-m-d'),
            ];

            // Динамическое добавление только выбранных метрик
            foreach ($selectedMetrics as $metricName) {
                switch ($metricName) {
                    // Первичные метрики
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
                        // Расчетные метрики
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

    private function dispatchReportGenerationTask(Report $report): void
    {
        if (null === $report->getId()) {
            $this->logger->critical('Cannot dispatch report generation task: Report ID is missing after flush.');
            throw new \LogicException('Report ID is missing after persist/flush, cannot dispatch task.');
        }

        // Parameters are already set on the $report entity as an array
        $taskPayload = [
            'reportId' => $report->getId(),
            'reportType' => $report->getType(),
            'parameters' => $report->getParameters(), // This will be the structured array
        ];

        // Generate absolute URL for the webhook
        $webhookUrl = $this->urlGenerator->generate(
            self::REPORT_WEBHOOK_ROUTE,
            [], // No route parameters for this webhook
            UrlGeneratorInterface::ABSOLUTE_PATH
        );

        try {
            $taskId = $this->rabbitMQProducerService->dispatchAsyncTaskWithWebhook(
                self::REPORT_GENERATION_TOPIC,
                $taskPayload,
                $webhookUrl
            );

            $this->logger->info('Report generation task dispatched successfully.', [
                'reportId' => $report->getId(),
                'topic' => self::REPORT_GENERATION_TOPIC,
                'taskId' => $taskId, // Assuming service returns a task ID
                'webhookUrl' => $webhookUrl,
                'payloadKeys' => array_keys($taskPayload['parameters'] ?? [])
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to dispatch report generation task to RabbitMQ.', [
                'reportId' => $report->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Re-throw as a runtime exception so the report creation might be rolled back or marked as failed dispatch
            throw new \RuntimeException('Failed to dispatch report generation task to message queue.', 0, $e);
        }
    }

    public function updateReportCompletionStatus(
        int $reportId,
        string $status,
        ?string $filePath = null,
        ?string $errorMessage = null
    ): Report {
        $report = $this->getReportById($reportId); // Throws EntityNotFoundException

        if (!in_array($status, [ReportStatusEnum::completed->value, ReportStatusEnum::cancelled->value])) { // Use constants if defined in Report entity
            throw new \InvalidArgumentException("Invalid completion status provided: '{$status}'. Must be 'completed' or 'failed'.");
        }

        // Prevent re-updating a terminal status if not desired
        if (in_array($report->getStatus(), [ReportStatusEnum::completed->value, ReportStatusEnum::cancelled->value])) {
            $this->logger->warning('Attempted to update status of an already finalized report.', [
                'reportId' => $reportId,
                'currentStatus' => $report->getStatus(),
                'newStatus' => $status
            ]);

            return $report; // Or throw an exception, depending on business rules
        }

        try {
            $report->setStatus($status);
            $report->setCompletedAt(new \DateTimeImmutable()); // Use immutable for safety

            if ($status === ReportStatusEnum::completed->value) {
                if (empty($filePath)) { // Check for empty string as well
                    throw new \InvalidArgumentException('File path must be provided for completed reports.');
                }
                $report->setFilePath($filePath);
                //$report->setErrorDetails(null); // Clear any previous error
                $this->logger->info('Report marked as completed.', ['reportId' => $reportId, 'filePath' => $filePath]);
            } else { // status === Report::STATUS_FAILED
                $report->setFilePath(null); // Ensure no file path for failed reports
                //$report->setErrorDetails($errorMessage ?? 'Report generation failed with an unknown error.'); // Add an error field to Report entity if needed
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
        $report = $this->getReportById($id); // Throws EntityNotFoundException

        try {
            // Attempt to delete the physical file if it exists
            $filePath = $report->getFilePath();
            if ($filePath && file_exists($filePath)) {
                if (!unlink($filePath)) {
                    $this->logger->warning('Could not delete report file from disk.', ['reportId' => $id, 'filePath' => $filePath]);
                    // Decide if this should prevent report entity deletion or just be logged.
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
    // validateReportData method is removed as validation is now handled by DTOs.
}
