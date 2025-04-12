<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Applicant;
use App\Entity\MarketingCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class ApplicantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Applicant::class);
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.applications', 'app')
            ->where('app.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    public function countByCampaign(MarketingCampaign $campaign): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.marketingCampaigns', 'mc')
            ->where('mc.id = :campaignId')
            ->setParameter('campaignId', $campaign->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCriteria(
        ?string $search = null,
        ?string $sortBy = 'createdAt',
        ?string $sortDirection = 'DESC',
        int $page = 1,
        int $limit = 10
    ): Paginator {
        $queryBuilder = $this->createQueryBuilder('a');

        if ($search) {
            $queryBuilder
                ->where('a.firstName LIKE :search OR a.lastName LIKE :search OR a.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $queryBuilder->orderBy("a.$sortBy", $sortDirection);

        $query = $queryBuilder->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($query);
    }
}
