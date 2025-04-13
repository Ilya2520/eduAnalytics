<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\MarketingCampaign;
use App\Service\MarketingCampaignService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Marketing Campaigns')]
#[Route('/api/marketing-campaigns')]
class MarketingCampaignController extends AbstractController
{
    public function __construct(
        private readonly MarketingCampaignService $marketingCampaignService
    ) {
    }

    #[OA\Get(
        summary: 'Get list of marketing campaigns',
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
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['planned', 'active', 'completed', 'cancelled'])
            ),
            new OA\Parameter(
                name: 'channel',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['email', 'social', 'ads', 'events'])
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'List of marketing campaigns',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: MarketingCampaign::class))
        )
    )]
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'marketing_campaigns_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $status = $request->query->get('status');
        $channel = $request->query->get('channel');

        $campaigns = $this->marketingCampaignService->getMarketingCampaignsList($page, $limit, $status, $channel);

        return $this->json($campaigns);
    }

    #[OA\Get(
        summary: 'Get marketing campaign by ID',
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
        description: 'Marketing campaign details',
        content: new OA\JsonContent(ref: new Model(type: MarketingCampaign::class))
    )]
    #[OA\Response(
        response: 404,
        description: 'Marketing campaign not found'
    )]
    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'marketing_campaign_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $campaign = $this->marketingCampaignService->getMarketingCampaignById($id);

        if (!$campaign) {
            return $this->json(['error' => 'Marketing campaign not found'], 404);
        }

        return $this->json($campaign);
    }

    #[OA\Post(
        summary: 'Create new marketing campaign',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Marketing campaign data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: MarketingCampaign::class, groups: ['create']))
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Marketing campaign created',
        content: new OA\JsonContent(ref: new Model(type: MarketingCampaign::class))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid data'
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'marketing_campaign_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $campaign = $this->marketingCampaignService->createMarketingCampaign($data);

            return $this->json($campaign, 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[OA\Put(
        summary: 'Update marketing campaign',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Marketing campaign data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: MarketingCampaign::class, groups: ['update']))
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
        description: 'Marketing campaign updated',
        content: new OA\JsonContent(ref: new Model(type: MarketingCampaign::class))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid data'
    )]
    #[OA\Response(
        response: 404,
        description: 'Marketing campaign not found'
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'marketing_campaign_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $campaign = $this->marketingCampaignService->updateMarketingCampaign($id, $data);

            return $this->json($campaign);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[OA\Delete(
        summary: 'Delete marketing campaign',
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
        description: 'Marketing campaign deleted'
    )]
    #[OA\Response(
        response: 404,
        description: 'Marketing campaign not found'
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'marketing_campaign_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->marketingCampaignService->deleteMarketingCampaign($id);

            return $this->json(null, 204);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }
}
