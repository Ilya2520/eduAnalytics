<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Report;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    public function findPendingReports(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedReportsByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.requestedBy = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'completed')
            ->orderBy('r.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
