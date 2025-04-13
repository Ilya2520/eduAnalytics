<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Output\MarketingCampaignOutputDTO;
use App\Entity\MarketingCampaign;
use App\Repository\MarketingCampaignRepository;
use Doctrine\ORM\EntityManagerInterface;
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
            $paginator = $this->repository->findByFilters($page, $limit, $status, $channel);

            $items = array_map(
                fn (MarketingCampaign $campaign) => MarketingCampaignOutputDTO::createFromEntity($campaign),
                iterator_to_array($paginator->getIterator())
            );

            return [
                'items' => $items,
                'total' => $paginator->count(),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($paginator->count() / $limit)
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error fetching marketing campaigns', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Could not fetch marketing campaigns');
        }
    }

    public function getMarketingCampaignById(int $id): ?MarketingCampaign
    {
        try {
            return $this->repository->find($id);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching marketing campaign', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Could not fetch marketing campaign');
        }
    }

    public function createMarketingCampaign(array $data): MarketingCampaign
    {
        $this->validateMarketingCampaignData($data);

        try {
            $campaign = new MarketingCampaign();
            $campaign->setName($data['name']);
            $campaign->setDescription($data['description'] ?? null);
            $campaign->setStartDate(new \DateTime($data['startDate']));
            $campaign->setEndDate(new \DateTime($data['endDate']));
            $campaign->setBudget((float) $data['budget']);
            $campaign->setStatus($data['status']);
            $campaign->setChannel($data['channel']);

            $this->em->persist($campaign);
            $this->em->flush();

            return $campaign;
        } catch (\Exception $e) {
            $this->logger->error('Error creating marketing campaign', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Could not create marketing campaign');
        }
    }

    public function updateMarketingCampaign(int $id, array $data): MarketingCampaign
    {
        $campaign = $this->getMarketingCampaignById($id);
        if (!$campaign) {
            throw new \RuntimeException('Marketing campaign not found');
        }

        $this->validateMarketingCampaignData($data, true);

        try {
            if (isset($data['name'])) {
                $campaign->setName($data['name']);
            }
            if (isset($data['description'])) {
                $campaign->setDescription($data['description']);
            }
            if (isset($data['startDate'])) {
                $campaign->setStartDate(new \DateTime($data['startDate']));
            }
            if (isset($data['endDate'])) {
                $campaign->setEndDate(new \DateTime($data['endDate']));
            }
            if (isset($data['budget'])) {
                $campaign->setBudget((float) $data['budget']);
            }
            if (isset($data['status'])) {
                $campaign->setStatus($data['status']);
            }
            if (isset($data['channel'])) {
                $campaign->setChannel($data['channel']);
            }

            $this->em->flush();

            return $campaign;
        } catch (\Exception $e) {
            $this->logger->error('Error updating marketing campaign', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Could not update marketing campaign');
        }
    }

    public function deleteMarketingCampaign(int $id): void
    {
        $campaign = $this->getMarketingCampaignById($id);
        if (!$campaign) {
            throw new \RuntimeException('Marketing campaign not found');
        }

        try {
            $this->em->remove($campaign);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error('Error deleting marketing campaign', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Could not delete marketing campaign');
        }
    }

    private function validateMarketingCampaignData(array $data, bool $isUpdate = false): void
    {
        $requiredFields = ['name', 'startDate', 'endDate', 'budget', 'status', 'channel'];

        foreach ($requiredFields as $field) {
            if (!$isUpdate && !array_key_exists($field, $data)) {
                throw new \InvalidArgumentException("Field $field is required");
            }
        }

        if (isset($data['status']) && !in_array($data['status'], ['planned', 'active', 'completed', 'cancelled'])) {
            throw new \InvalidArgumentException('Invalid status value');
        }

        if (isset($data['channel']) && !in_array($data['channel'], ['email', 'social', 'ads', 'events'])) {
            throw new \InvalidArgumentException('Invalid channel value');
        }

        if (isset($data['startDate']) && isset($data['endDate'])) {
            $startDate = new \DateTime($data['startDate']);
            $endDate = new \DateTime($data['endDate']);

            if ($startDate > $endDate) {
                throw new \InvalidArgumentException('End date must be after start date');
            }
        }

        if (isset($data['budget']) && $data['budget'] <= 0) {
            throw new \InvalidArgumentException('Budget must be positive');
        }
    }
}
