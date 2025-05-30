<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Input\MarketingCampaignCreateDTO;
use App\DTO\Input\MarketingCampaignUpdateDTO;
use App\DTO\Output\MarketingCampaignOutputDTO;
use App\Entity\MarketingCampaign;
use App\Repository\MarketingCampaignRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\Pagination\Paginator; // Assuming usage for pagination
use Psr\Log\LoggerInterface;

class MarketingCampaignService
{
    public function __construct(
        private readonly MarketingCampaignRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getMarketingCampaignsList(
        int $page,
        int $limit,
        ?string $status = null,
        ?string $channel = null
    ): array {
        try {
            // Assuming repository->findByFilters returns a Doctrine Paginator
            /** @var Paginator<MarketingCampaign> $paginator */
            $paginator = $this->repository->findByFilters($page, $limit, $status, $channel);

            $items = array_map(
                fn (MarketingCampaign $campaign) => MarketingCampaignOutputDTO::createFromEntity($campaign),
                iterator_to_array($paginator->getIterator())
            );

            $totalItems = $paginator->count();
            $pages = ($limit > 0 && $totalItems > 0) ? (int)ceil($totalItems / $limit) : 1;


            return [
                'items' => $items,
                'total' => $totalItems,
                'page' => $page,
                'limit' => $limit,
                'pages' => $pages,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error fetching marketing campaigns list', [
                'page' => $page, 'limit' => $limit, 'status' => $status, 'channel' => $channel,
                'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Could not fetch marketing campaigns list. Please try again later.');
        }
    }

    public function getMarketingCampaignById(int $id): MarketingCampaign // Return entity for internal use
    {
        $campaign = $this->repository->find($id);
        if (!$campaign) {
            $this->logger->warning('Marketing campaign not found by ID.', ['id' => $id]);
            throw new EntityNotFoundException('Marketing campaign not found with ID: ' . $id);
        }

        return $campaign;
    }

    public function createMarketingCampaign(MarketingCampaignCreateDTO $dto): MarketingCampaign
    {
        // Cross-field validation (e.g. endDate >= startDate) is handled by DTO annotations.
        try {
            $campaign = new MarketingCampaign();
            $campaign->setName($dto->name);
            $campaign->setDescription($dto->description);
            $campaign->setStartDate($dto->startDate);
            $campaign->setEndDate($dto->endDate);
            $campaign->setBudget($dto->budget);
            $campaign->setStatus($dto->status);
            $campaign->setChannel($dto->channel);

            $this->em->persist($campaign);
            $this->em->flush();

            $this->logger->info('Marketing campaign created successfully.', ['campaignId' => $campaign->getId()]);

            return $campaign;
        } catch (\Exception $e) {
            $this->logger->error('Error creating marketing campaign', [
                'dto' => (array)$dto, // Be cautious logging full DTOs if they contain sensitive data
                'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Could not create marketing campaign due to an internal error.');
        }
    }

    public function updateMarketingCampaign(int $id, MarketingCampaignUpdateDTO $dto): MarketingCampaign
    {
        $campaign = $this->getMarketingCampaignById($id); // Throws EntityNotFoundException if not found

        try {
            $updated = false;
            if ($dto->name !== null) {
                $campaign->setName($dto->name);
                $updated = true;
            }
            if ($dto->description !== null) {
                $campaign->setDescription($dto->description);
                $updated = true;
            }
            if ($dto->startDate !== null) {
                $campaign->setStartDate($dto->startDate);
                $updated = true;
            }
            if ($dto->endDate !== null) {
                $campaign->setEndDate($dto->endDate);
                $updated = true;
            }
            if ($dto->budget !== null) {
                $campaign->setBudget($dto->budget);
                $updated = true;
            }
            if ($dto->status !== null) {
                $campaign->setStatus($dto->status);
                $updated = true;
            }
            if ($dto->channel !== null) {
                $campaign->setChannel($dto->channel);
                $updated = true;
            }

            // Service-level validation for complex rules after merging, if DTO validation is not enough
            if ($campaign->getStartDate() > $campaign->getEndDate()) {
                throw new \InvalidArgumentException('End date must be on or after the start date.');
            }

            if ($updated) {
                $this->em->flush();
                $this->logger->info('Marketing campaign updated successfully.', ['campaignId' => $id]);
            }

            return $campaign;
        } catch (\InvalidArgumentException $e) { // Catch specific validation errors from service
            $this->logger->warning('Invalid data during marketing campaign update.', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Error updating marketing campaign', [
                'id' => $id, 'dto' => (array)$dto,
                'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Could not update marketing campaign due to an internal error.');
        }
    }

    public function deleteMarketingCampaign(int $id): void
    {
        $campaign = $this->getMarketingCampaignById($id); // Throws EntityNotFoundException if not found

        try {
            $this->em->remove($campaign);
            $this->em->flush();
            $this->logger->info('Marketing campaign deleted successfully.', ['campaignId' => $id]);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting marketing campaign', [
                'id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Could not delete marketing campaign due to an internal error.');
        }
    }
}
