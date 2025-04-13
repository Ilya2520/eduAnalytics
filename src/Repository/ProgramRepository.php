<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Program;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class ProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Program::class);
    }

    public function findByFilters(
        int $page = 1,
        int $limit = 10,
        ?bool $isActive = null,
        ?string $degree = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('p');

        if ($isActive !== null) {
            $queryBuilder
                ->andWhere('p.isActive = :isActive')
                ->setParameter('isActive', $isActive);
        }

        if ($degree !== null) {
            $queryBuilder
                ->andWhere('p.degree = :degree')
                ->setParameter('degree', $degree);
        }

        $query = $queryBuilder
            ->orderBy('p.name', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        $paginator = new Paginator($query);

        return [
            'items' => $paginator->getIterator(),
            'total' => count($paginator),
            'page' => $page,
            'limit' => $limit
        ];
    }

}
