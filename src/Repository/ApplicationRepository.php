<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Application;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    public function getStatisticsByProgram(): array
    {
        return $this->createQueryBuilder('a')
            ->select('p.name as program_name, COUNT(a.id) as applications_count')
            ->join('a.program', 'p')
            ->groupBy('p.id')
            ->getQuery()
            ->getResult();
    }

    public function findByStatusAndDateRange(string $status, \DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.createdAt BETWEEN :start AND :end')
            ->setParameter('status', $status)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    public function findByCriteria(
        ?string $status = null,
        ?int $applicantId = null,
        ?int $programId = null,
        string $sortBy = 'createdAt',
        string $sortDirection = 'DESC',
        int $page = 1,
        int $limit = 10
    ): Paginator {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('applicant')
            ->addSelect('program')
            ->leftJoin('a.applicant', 'applicant')
            ->leftJoin('a.program', 'program');

        if ($status) {
            $queryBuilder->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        if ($applicantId) {
            $queryBuilder->andWhere('applicant.id = :applicantId')
                ->setParameter('applicantId', $applicantId);
        }

        if ($programId) {
            $queryBuilder->andWhere('program.id = :programId')
                ->setParameter('programId', $programId);
        }

        $queryBuilder->orderBy("a.$sortBy", $sortDirection);

        $query = $queryBuilder->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($query);
    }
}
