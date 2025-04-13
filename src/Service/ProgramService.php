<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Program;
use App\Repository\ProgramRepository;

class ProgramService
{
    public function __construct(
        private readonly ProgramRepository $programRepository
    ) {
    }

    public function getProgramsList(
        int $page = 1,
        int $limit = 10,
        ?bool $isActive = null,
        ?string $degree = null
    ): array {
        return $this->programRepository->findByFilters(
            $page,
            $limit,
            $isActive,
            $degree
        );
    }

    public function getProgramById(int $id): ?Program
    {
        return $this->programRepository->find($id);
    }
}
