<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Input\ReportCreateInputDTO;
use App\DTO\Output\PaginatedResponseDTO;
use App\DTO\Output\ReportOutputDTO;
use App\Service\CacheService;
use App\Service\ReportService;
use Doctrine\ORM\EntityNotFoundException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Reports', description: 'Manage and download generated reports.')]
#[Route('/api/reports')]
class ReportController extends AbstractController
{
    private const TAG = 'Reports';
    public function __construct(
        private readonly ReportService $reportService,
        private readonly LoggerInterface $logger,
        private readonly CacheService $cacheService,
    ) {
    }

    #[OA\Get(
        summary: 'List available reports',
        description: 'Retrieves a paginated list of reports, filterable by type and status.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
            new OA\Parameter(name: 'limit', description: 'Number of reports per page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'type', description: 'Filter by report type', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'campaign_metrics_summary')),
            new OA\Parameter(name: 'status', description: 'Filter by report status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'processing', 'completed', 'failed']))
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'A paginated list of reports.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: new Model(type: PaginatedResponseDTO::class))],
            properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: ReportOutputDTO::class)))
            ]
        )
    )]
    #[OA\Response(response: 500, description: 'Internal server error.')]
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'reports_list', methods: ['GET'])]
    public function list(
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] ?string $status = null
    ): JsonResponse {
        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        try {
            $cacheKey = $this->cacheService->generateCacheKey(
                className: get_class($this),
                prefix: __FUNCTION__,
                params: [
                    'page' => $page,
                    'limit' => $limit,
                    'type' => $type,
                    'status' => $status,
                ]
            );

            $reportsData = $this->cacheService->fetchFromCache(
                key: $cacheKey,
                tag: self::TAG,
                callback: fn () => $this->reportService->getReportsList($page, $limit, $type, $status),
            );

            return $this->json($reportsData);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        summary: 'Get a specific report by ID',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID of the report', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ]
    )]
    #[OA\Response(response: 200, description: 'Details of the report.', content: new OA\JsonContent(ref: new Model(type: ReportOutputDTO::class)))]
    #[OA\Response(response: 404, description: 'Report not found.')]
    #[OA\Response(response: 500, description: 'Internal server error.')]
    #[IsGranted('ROLE_USER')]
    #[Route('/{id<\d+>}', name: 'report_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateCacheKey(
                className: get_class($this),
                prefix: __FUNCTION__,
                params: ['id' => $id]
            );

            $result = $this->cacheService->fetchFromCache(
                key: $cacheKey,
                tag: self::TAG,
                callback: fn () => $this->reportService->getReportById($id),
            );

            return $this->json(ReportOutputDTO::createFromEntity($result));
        } catch (EntityNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) { // Catch other potential runtime issues from service
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        summary: 'Create a new report request',
        description: "Queues a new report for generation based on the provided parameters. The 'parameters' field is crucial and defines the report's content.",
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Data for the new report, including name, type, and detailed generation parameters.',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: ReportCreateInputDTO::class))
        )
    )]
    #[OA\Response(response: 202, description: 'Report request accepted and queued for generation.', content: new OA\JsonContent(ref: new Model(type: ReportOutputDTO::class)))] // 202 Accepted for async tasks
    #[OA\Response(response: 400, description: 'Invalid input data (e.g., service-level validation failure).')]
    #[OA\Response(response: 422, description: 'Validation failed (e.g., DTO validation for types, missing fields, format issues).')]
    #[OA\Response(response: 500, description: 'Internal server error during request processing or task dispatch.')]
    #[IsGranted('ROLE_USER')] // Or ROLE_ADMIN depending on who can create reports
    #[Route('', name: 'report_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] ReportCreateInputDTO $dto): JsonResponse
    {
        try {
            $report = $this->reportService->createReport($dto);
            // For async, 202 Accepted is often more appropriate than 201 Created.
            // The resource (report metadata) is created, but the full report file is pending.
            $this->cacheService->invalidateByTags([self::TAG]);

            return $this->json(ReportOutputDTO::createFromEntity($report), Response::HTTP_ACCEPTED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) { // Catch errors from service (e.g., dispatch failure)
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        summary: 'Delete a report',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID of the report to delete', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ]
    )]
    #[OA\Response(response: 204, description: 'Report deleted successfully.')]
    #[OA\Response(response: 404, description: 'Report not found.')]
    #[OA\Response(response: 500, description: 'Internal server error.')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id<\d+>}', name: 'report_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->reportService->deleteReport($id);
            $this->cacheService->invalidateByTags([self::TAG]);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (EntityNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        summary: 'Download a generated report file',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID of the report to download', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ]
    )]
    #[OA\Response(response: 200, description: 'Report file stream.', content: [new OA\MediaType(mediaType: 'application/octet-stream'), new OA\MediaType(mediaType: 'application/pdf'), new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')])] // Example content types
    #[OA\Response(response: 404, description: 'Report not found or file not available.')]
    #[OA\Response(response: 422, description: 'Report is not yet completed.')]
    #[OA\Response(response: 500, description: 'Internal server error preparing file.')]
    #[IsGranted('ROLE_USER')]
    #[Route('/{id<\d+>}/download', name: 'report_download', methods: ['GET'])]
    public function download(int $id): Response
    {
        try {
            $report = $this->reportService->getReportById($id); // Throws 404 if not found

            if ($report->getStatus() !== 'completed') { // Use constant if defined
                return $this->json(['error' => 'Report is not completed yet.', 'status' => $report->getStatus()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $filePath = $report->getFilePath();
            if (empty($filePath) || !file_exists($filePath)) {
                $this->logger->error('Report file is missing or inaccessible on disk.', ['reportId' => $id, 'filePathAttempted' => $filePath]);

                return $this->json(['error' => 'Report file is missing or inaccessible.'], Response::HTTP_INTERNAL_SERVER_ERROR); // Or 404 if preferred for missing file
            }

            $response = new BinaryFileResponse($filePath);
            // Suggest a filename to the browser.
            $suggestedFilename = $report->getName() . '_' . $report->getCreatedAt()->format('Ymd') . '.' . pathinfo($filePath, PATHINFO_EXTENSION);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $suggestedFilename);

            return $response;

        } catch (EntityNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) { // Catch-all for unexpected errors during file serving
            $this->logger->error('Error preparing report file for download.', ['reportId' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return $this->json(['error' => 'Could not prepare report file for download.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        summary: 'Webhook for report generation completion status',
        description: 'Internal endpoint called by the report generation worker upon completion or failure of a task. **This endpoint should be secured.**',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reportId', 'status'],
                properties: [
                    new OA\Property(property: 'taskId', type: 'string', description: 'ID of the generation task.', example: 'task_123xyz'),
                    new OA\Property(property: 'reportId', type: 'integer', description: 'ID of the report being updated.', example: 1),
                    new OA\Property(property: 'status', type: 'string', enum: ['completed', 'failed'], description: 'Final status of the report generation.'),
                    new OA\Property(property: 'filePath', type: 'string', nullable: true, description: "Path to the generated file if status is 'completed'.", example: '/reports/generated/report_1.xlsx'),
                    new OA\Property(property: 'error', type: 'string', nullable: true, description: "Error message if status is 'failed'.", example: 'Data source unavailable.')
                ],
                type: 'object'
            )
        )
    )]
    #[OA\Response(response: 200, description: 'Webhook acknowledged and processed.')]
    #[OA\Response(response: 400, description: 'Invalid webhook payload.')]
    #[OA\Response(response: 403, description: 'Forbidden (e.g., if signature validation fails).')]
    #[OA\Response(response: 404, description: 'Report specified in webhook not found.')]
    #[OA\Response(response: 500, description: 'Internal server error processing webhook.')]
    #[Route('/webhooks/report-completed', name: 'webhook_report_completed', methods: ['POST'])] // Route name used by service
    public function handleReportCompletionWebhook(Request $request): JsonResponse
    {
        // IMPORTANT: Implement robust security for this webhook (e.g., IP whitelisting, shared secret, signature).
        // Example (conceptual - adapt to your security strategy):
        // $expectedSignature = hash_hmac('sha256', $request->getContent(), $this->getParameter('webhook_secret'));
        // if (!hash_equals($expectedSignature, $request->headers->get('X-Webhook-Signature'))) {
        //     $this->logger->warning('Invalid webhook signature attempt.');
        //     return $this->json(['error' => 'Forbidden: Invalid signature.'], Response::HTTP_FORBIDDEN);
        // }

        try {
            $data = $request->toArray(); // Throws exception on invalid JSON
        } catch (\JsonException $e) {
            $this->logger->error('Webhook received invalid JSON payload.', ['error' => $e->getMessage()]);

            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        $reportId = filter_var($data['reportId'] ?? null, FILTER_VALIDATE_INT);
        $status = $data['status'] ?? null;
        $filePath = $data['filePath'] ?? null; // Path should be relative to a configured root or absolute if worker guarantees it
        $error = $data['error'] ?? null;
        $taskId = $data['taskId'] ?? 'N/A';


        if ($reportId === false || $reportId === null || empty($status) || !in_array($status, ['completed', 'failed'])) {
            $this->logger->error('Invalid webhook payload: Missing or invalid reportId/status.', ['payload' => $data]);

            return $this->json(['error' => 'Invalid payload: reportId and status (completed/failed) are required.'], Response::HTTP_BAD_REQUEST);
        }
        if ($status === 'completed' && empty($filePath)) {
            $this->logger->error("Webhook payload for 'completed' status is missing 'filePath'.", ['payload' => $data]);

            return $this->json(['error' => "Invalid payload: 'filePath' is required for completed status."], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->reportService->updateReportCompletionStatus($reportId, $status, $filePath, $error);
            $this->logger->info('Report status updated via webhook successfully.', ['reportId' => $reportId, 'newStatus' => $status, 'taskId' => $taskId]);
            $this->cacheService->invalidateByTags([self::TAG]);

            return $this->json(['message' => 'Webhook processed successfully.']);
        } catch (EntityNotFoundException $e) {
            $this->logger->warning('Report not found during webhook processing.', ['reportId' => $reportId, 'taskId' => $taskId, 'error' => $e->getMessage()]);

            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Invalid data passed to service during webhook processing.', ['reportId' => $reportId, 'taskId' => $taskId, 'error' => $e->getMessage()]);

            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            $this->logger->error('Runtime error during webhook processing.', ['reportId' => $reportId, 'taskId' => $taskId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return $this->json(['error' => 'Internal server error processing webhook.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
