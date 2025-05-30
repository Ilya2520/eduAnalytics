<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Input\MarketingCampaignCreateDTO;
use App\DTO\Input\MarketingCampaignUpdateDTO;
use App\DTO\Output\MarketingCampaignOutputDTO;
use App\DTO\Output\PaginatedResponseDTO;
use App\Service\CacheService;
use App\Service\MarketingCampaignService;
use Doctrine\ORM\EntityNotFoundException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Marketing Campaigns', description: 'Manage marketing campaigns and their details.')]
#[Route('/api/marketing-campaigns')]
class MarketingCampaignController extends AbstractController
{
    private const TAG = 'Marketing-campaigns';
    public function __construct(
        private readonly MarketingCampaignService $marketingCampaignService,
        private readonly CacheService $cacheService,
    ) {
    }

    #[OA\Get(
        summary: 'List marketing campaigns',
        description: 'Retrieves a paginated list of marketing campaigns, with optional filtering by status and channel.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1), description: 'Page number to retrieve.'),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, minimum: 1, maximum: 100), description: 'Number of items per page.'),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['planned', 'active', 'completed', 'cancelled']), description: 'Filter by campaign status.'),
            new OA\Parameter(name: 'channel', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['email', 'social', 'ads', 'events']), description: 'Filter by campaign channel.')
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'A paginated list of marketing campaigns.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: new Model(type: PaginatedResponseDTO::class))], # Generic pagination structure
            properties: [ # Specific item type
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: MarketingCampaignOutputDTO::class)))
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Invalid query parameters.')]
    #[OA\Response(response: 500, description: 'Internal server error.')]
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'marketing_campaigns_list', methods: ['GET'])]
    public function list(
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] ?string $status = null,
        #[MapQueryParameter] ?string $channel = null
    ): JsonResponse {
        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > 100) {
            $limit = 100;
        } // Max limit example

        try {
            $cacheKey = $this->cacheService->generateCacheKey(
                className: get_class($this),
                prefix: __FUNCTION__,
                params: [
                    'page' => $page,
                    'limit' => $limit,
                    'status' => $status,
                    'channel' => $channel
                ],
            );

            $campaignsData = $this->cacheService->fetchFromCache(
                key: $cacheKey,
                tag: self::TAG,
                callback: fn () => $this->marketingCampaignService->getMarketingCampaignsList($page, $limit, $status, $channel)
            );

            return $this->json($campaignsData);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        summary: 'Get a specific marketing campaign by ID',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1), description: 'ID of the marketing campaign.')
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Details of the marketing campaign.',
        content: new OA\JsonContent(ref: new Model(type: MarketingCampaignOutputDTO::class))
    )]
    #[OA\Response(response: 404, description: 'Marketing campaign not found.')]
    #[OA\Response(response: 500, description: 'Internal server error.')]
    #[IsGranted('ROLE_USER')]
    #[Route('/{id<\d+>}', name: 'marketing_campaign_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateCacheKey(
                className: get_class($this),
                prefix: __FUNCTION__,
                params: ['id' => $id]
            );

            $campaign = $this->cacheService->fetchFromCache(
                key: $cacheKey,
                tag: self::TAG,
                callback: fn () => $this->marketingCampaignService->getMarketingCampaignById($id)
            );

            return $this->json(MarketingCampaignOutputDTO::createFromEntity($campaign));
        } catch (EntityNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        summary: 'Create a new marketing campaign',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Data for the new marketing campaign.',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: MarketingCampaignCreateDTO::class))
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Marketing campaign created successfully.',
        content: new OA\JsonContent(ref: new Model(type: MarketingCampaignOutputDTO::class))
    )]
    #[OA\Response(response: 400, description: 'Invalid input data provided (service-level validation).')]
    #[OA\Response(response: 422, description: 'Validation failed (DTO validation for types, missing fields, etc.).')]
    #[OA\Response(response: 500, description: 'Internal server error.')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'marketing_campaign_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] MarketingCampaignCreateDTO $dto): JsonResponse
    {
        try {
            $campaign = $this->marketingCampaignService->createMarketingCampaign($dto);
            $this->cacheService->invalidateByTags([self::TAG]);

            return $this->json(MarketingCampaignOutputDTO::createFromEntity($campaign), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) { // For specific service-level validation errors
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Put(
        summary: 'Update an existing marketing campaign',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1), description: 'ID of the marketing campaign to update.')
        ],
        requestBody: new OA\RequestBody(
            description: 'Data to update for the marketing campaign. Only provided fields will be updated.',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: MarketingCampaignUpdateDTO::class))
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Marketing campaign updated successfully.',
        content: new OA\JsonContent(ref: new Model(type: MarketingCampaignOutputDTO::class))
    )]
    #[OA\Response(response: 400, description: 'Invalid input data (service-level validation).')]
    #[OA\Response(response: 404, description: 'Marketing campaign not found.')]
    #[OA\Response(response: 422, description: 'Validation failed (DTO validation).')]
    #[OA\Response(response: 500, description: 'Internal server error.')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id<\d+>}', name: 'marketing_campaign_update', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] MarketingCampaignUpdateDTO $dto): JsonResponse
    {
        try {
            $campaign = $this->marketingCampaignService->updateMarketingCampaign($id, $dto);
            $this->cacheService->invalidateByTags([self::TAG]);

            return $this->json(MarketingCampaignOutputDTO::createFromEntity($campaign));
        } catch (EntityNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        summary: 'Delete a marketing campaign',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1), description: 'ID of the marketing campaign to delete.')
        ]
    )]
    #[OA\Response(response: 204, description: 'Marketing campaign deleted successfully.')]
    #[OA\Response(response: 404, description: 'Marketing campaign not found.')]
    #[OA\Response(response: 500, description: 'Internal server error.')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id<\d+>}', name: 'marketing_campaign_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->marketingCampaignService->deleteMarketingCampaign($id);
            $this->cacheService->invalidateByTags([self::TAG]);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (EntityNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
