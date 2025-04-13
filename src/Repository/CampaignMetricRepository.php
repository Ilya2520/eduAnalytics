<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CampaignMetricRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CampaignMetricRepository::class);
    }

    public function findByCampaignAndDateRange(
        int $campaignId,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('cm')
            ->andWhere('cm.campaign = :campaignId')
            ->setParameter('campaignId', $campaignId)
            ->orderBy('cm.recordDate', 'ASC');

        if ($startDate !== null) {
            $queryBuilder->andWhere('cm.recordDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $queryBuilder->andWhere('cm.recordDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findLatestForCampaign(int $campaignId, int $limit = 10): array
    {
        return $this->createQueryBuilder('cm')
            ->andWhere('cm.campaign = :campaignId')
            ->setParameter('campaignId', $campaignId)
            ->orderBy('cm.recordDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
