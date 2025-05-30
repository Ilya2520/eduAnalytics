<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Input\CampaignMetricCreateDTO;
use App\DTO\Input\CampaignMetricUpdateDTO;
use App\DTO\Output\CampaignMetricOutputDTO;
use App\Service\CacheService;
use App\Service\CampaignMetricService;
use Doctrine\ORM\EntityNotFoundException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Campaign Metrics', description: 'Manage campaign performance metrics')]
#[Route('/api/campaign-metrics')]
class CampaignMetricController extends AbstractController
{
    private const TAG = 'CampaignMetric';
    public function __construct(
        private readonly CampaignMetricService $campaignMetricService,
        private readonly CacheService $cacheService,
    ) {
    }

    #[OA\Get(
        description: 'Retrieves a list of campaign metrics, optionally filtered by campaign ID and date range.',
        summary: 'List campaign metrics',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'campaignId',
                description: 'ID of the marketing campaign',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'startDate',
                description: 'Filter metrics from this date (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-01')
            ),
            new OA\Parameter(
                name: 'endDate',
                description: 'Filter metrics up to this date (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-31')
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'A list of campaign metrics.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: CampaignMetricOutputDTO::class))
        )
    )]
    #[OA\Response(response: 400, description: 'Invalid input parameters (e.g., missing campaignId, invalid date format).')]
    #[OA\Response(response: 500, description: 'Internal server error while fetching metrics.')]
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'campaign_metrics_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $campaignIdStr = $request->query->get('campaignId');
        if (null === $campaignIdStr || !ctype_digit($campaignIdStr) || (int)$campaignIdStr <= 0) {
            return $this->json(['error' => 'A valid positive integer campaignId query parameter is required.'], Response::HTTP_BAD_REQUEST);
        }
        $campaignId = (int)$campaignIdStr;

        $startDateStr = $request->query->get('startDate');
        $endDateStr = $request->query->get('endDate');
        $startDate = null;
        $endDate = null;

        try {
            if ($startDateStr) {
                $startDate = new \DateTimeImmutable($startDateStr);
            }
            if ($endDateStr) {
                $endDate = new \DateTimeImmutable($endDateStr);
            }
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format for startDate or endDate. Please use YYYY-MM-DD.'], Response::HTTP_BAD_REQUEST);
        }

        if ($startDate && $endDate && $startDate > $endDate) {
            return $this->json(['error' => 'startDate cannot be after endDate.'], Response::HTTP_BAD_REQUEST);
        }

        try {

            $cacheKey = $this->cacheService->generateCacheKey(
                className: get_class($this),
                prefix: __FUNCTION__,
                params: [
                    'campaignId' => $campaignId,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ]
            );

            $metrics = $this->cacheService->fetchFromCache(
                key: $cacheKey,
                tag: self::TAG,
                callback: fn () => $this->campaignMetricService->getMetricsByCampaign(
                    $campaignId,
                    $startDate,
                    $endDate
                ),
            );

            return $this->json($metrics);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        summary: 'Create a new campaign metric',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Data for the new campaign metric.',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CampaignMetricCreateDTO::class))
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Campaign metric created successfully.',
        content: new OA\JsonContent(ref: new Model(type: CampaignMetricOutputDTO::class))
    )]
    #[OA\Response(response: 400, description: 'Invalid input (e.g., campaign not found). Handled by service exceptions.')]
    #[OA\Response(response: 422, description: 'Validation failed (e.g., missing fields, type errors). Handled by Symfony for #[MapRequestPayload].')]
    #[OA\Response(response: 500, description: 'Internal server error while creating the metric.')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'campaign_metric_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CampaignMetricCreateDTO $dto): JsonResponse
    {
        try {
            $metricEntity = $this->campaignMetricService->createMetric($dto);
            $this->cacheService->invalidateByTags([self::TAG]);

            return $this->json(CampaignMetricOutputDTO::createFromEntity($metricEntity), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) { // E.g., Campaign not found
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Put(
        summary: 'Update an existing campaign metric',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the campaign metric to update.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            description: 'Data to update for the campaign metric. Only provided fields will be updated.',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CampaignMetricUpdateDTO::class))
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Campaign metric updated successfully.',
        content: new OA\JsonContent(ref: new Model(type: CampaignMetricOutputDTO::class))
    )]
    #[OA\Response(response: 404, description: 'Campaign metric not found.')]
    #[OA\Response(response: 422, description: 'Validation failed. Handled by Symfony for #[MapRequestPayload].')]
    #[OA\Response(response: 500, description: 'Internal server error while updating the metric.')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id<\d+>}', name: 'campaign_metric_update', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] CampaignMetricUpdateDTO $dto): JsonResponse
    {
        try {
            $metricEntity = $this->campaignMetricService->updateMetric($id, $dto);
            $this->cacheService->invalidateByTags([self::TAG]);

            return $this->json(CampaignMetricOutputDTO::createFromEntity($metricEntity));
        } catch (EntityNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        summary: 'Delete a campaign metric',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the campaign metric to delete.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ]
    )]
    #[OA\Response(response: 204, description: 'Campaign metric deleted successfully.')]
    #[OA\Response(response: 404, description: 'Campaign metric not found.')]
    #[OA\Response(response: 500, description: 'Internal server error while deleting the metric.')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id<\d+>}', name: 'campaign_metric_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->campaignMetricService->deleteMetric($id);
            $this->cacheService->invalidateByTags([self::TAG]);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (EntityNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
