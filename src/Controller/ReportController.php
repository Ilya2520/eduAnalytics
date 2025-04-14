<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Output\ReportOutputDTO;
use App\Entity\Report;
use App\Service\ReportService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Reports')]
#[Route('/api/reports')]
class ReportController extends AbstractController
{
    public function __construct(
        private readonly ReportService $reportService
    ) {
    }

    #[OA\Get(
        summary: 'Get list of reports',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
            new OA\Parameter(
                name: 'type',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['pending', 'processing', 'completed', 'failed'])
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'List of reports',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Report::class))
        )
    )]
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'reports_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $type = $request->query->get('type');
        $status = $request->query->get('status');

        $reports = $this->reportService->getReportsList($page, $limit, $type, $status);

        return $this->json($reports);
    }

    #[OA\Get(
        summary: 'Get report by ID',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Report details',
        content: new OA\JsonContent(ref: new Model(type: Report::class))
    )]
    #[OA\Response(
        response: 404,
        description: 'Report not found'
    )]
    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'report_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $report = $this->reportService->getReportById($id);

        if (!$report) {
            return $this->json(['error' => 'Report not found'], 404);
        }

        return $this->json(ReportOutputDTO::createFromEntity($report));
    }

    #[OA\Post(
        summary: 'Create new report',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Report data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: Report::class, groups: ['create']))
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Report created',
        content: new OA\JsonContent(ref: new Model(type: Report::class))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid data'
    )]
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'report_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $report = $this->reportService->createReport($data);

            return $this->json($report, 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[OA\Put(
        summary: 'Update report',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: 'Report data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: Report::class, groups: ['update']))
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Report updated',
        content: new OA\JsonContent(ref: new Model(type: Report::class))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid data'
    )]
    #[OA\Response(
        response: 404,
        description: 'Report not found'
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'report_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $report = $this->reportService->updateReport($id, $data);

            return $this->json($report);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[OA\Delete(
        summary: 'Delete report',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ]
    )]
    #[OA\Response(
        response: 204,
        description: 'Report deleted'
    )]
    #[OA\Response(
        response: 404,
        description: 'Report not found'
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'report_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->reportService->deleteReport($id);

            return $this->json(null, 204);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[OA\Get(
        summary: 'Download report file',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Report file',
        content: new OA\MediaType(mediaType: 'application/octet-stream')
    )]
    #[OA\Response(
        response: 404,
        description: 'Report not found or file not available'
    )]
    #[IsGranted('ROLE_USER')]
    #[Route('/{id}/download', name: 'report_download', methods: ['GET'])]
    public function download(int $id): Response
    {

    }
}
