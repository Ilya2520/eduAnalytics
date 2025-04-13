<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MarketingCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class MarketingCampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarketingCampaign::class);
    }

    public function findActiveCampaigns(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('mc')
            ->where('mc.startDate <= :now')
            ->andWhere('mc.endDate >= :now')
            ->andWhere('mc.status = :status')
            ->setParameter('now', $now)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }

    public function getCampaignEffectiveness(): array
    {
        return $this->createQueryBuilder('mc')
            ->select('mc.name, mc.channel,
                     SUM(cm.applicationsGenerated) as totalApplications,
                     mc.budget,
                     (mc.budget / SUM(cm.applicationsGenerated)) as costPerApplication')
            ->join('mc.metrics', 'cm')
            ->groupBy('mc.id')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(int $page, int $limit, ?string $status, ?string $channel): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('mc')
            ->orderBy('mc.startDate', 'DESC');

        if ($status !== null) {
            $queryBuilder->andWhere('mc.status = :status')
                ->setParameter('status', $status);
        }

        if ($channel !== null) {
            $queryBuilder->andWhere('mc.channel = :channel')
                ->setParameter('channel', $channel);
        }

        $query = $queryBuilder->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($query);
    }
}
