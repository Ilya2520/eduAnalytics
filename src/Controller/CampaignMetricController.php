<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CampaignMetric;
use App\Service\CampaignMetricService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Campaign Metrics')]
#[Route('/api/campaign-metrics')]
class CampaignMetricController extends AbstractController
{
    public function __construct(
        private readonly CampaignMetricService $campaignMetricService
    ) {
    }

    #[OA\Get(
        summary: 'Get metrics by campaign ID',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'campaignId',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'startDate',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'endDate',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'List of campaign metrics',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: CampaignMetric::class))
        )
    )]
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'campaign_metrics_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $campaignId = $request->query->getInt('campaignId');
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');

        $metrics = $this->campaignMetricService->getMetricsByCampaign(
            $campaignId,
            $startDate ? new \DateTime($startDate) : null,
            $endDate ? new \DateTime($endDate) : null
        );

        return $this->json($metrics);
    }

    #[OA\Post(
        summary: 'Create new campaign metric',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Campaign metric data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CampaignMetric::class, groups: ['create']))
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Created campaign metric',
        content: new OA\JsonContent(ref: new Model(type: CampaignMetric::class))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid data'
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'campaign_metric_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $metric = $this->campaignMetricService->createMetric($data);

            return $this->json($metric, 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[OA\Put(
        summary: 'Update campaign metric',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Campaign metric data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CampaignMetric::class, groups: ['update']))
        ),
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
        description: 'Updated campaign metric',
        content: new OA\JsonContent(ref: new Model(type: CampaignMetric::class))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid data'
    )]
    #[OA\Response(
        response: 404,
        description: 'Metric not found'
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'campaign_metric_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $metric = $this->campaignMetricService->updateMetric($id, $data);

            return $this->json($metric);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[OA\Delete(
        summary: 'Delete campaign metric',
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
        description: 'Metric deleted'
    )]
    #[OA\Response(
        response: 404,
        description: 'Metric not found'
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'campaign_metric_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->campaignMetricService->deleteMetric($id);

            return $this->json(null, 204);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }
}
