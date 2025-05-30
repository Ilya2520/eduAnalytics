<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Output\ProgramOutput;
use App\Entity\Program;
use App\Repository\ProgramRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ProgramService
{
    public function __construct(
        private readonly ProgramRepository $programRepository
    ) {
    }

    // Original method returning entities in the items array (or paginator)
    public function getProgramsList(
        int $page = 1,
        int $limit = 10,
        ?bool $isActive = null,
        ?string $degree = null
    ): array { // This returns the raw paginator structure from repository
        return $this->programRepository->findByFilters($page, $limit, $isActive, $degree);
    }

    // New suggested method that returns DTOs directly in the paginated structure
    public function getProgramsListWithOutputDTOs(
        int $page = 1,
        int $limit = 10,
        ?bool $isActive = null,
        ?string $degree = null
    ): array {
        $paginatorData = $this->programRepository->findByFilters($page, $limit, $isActive, $degree);
        // Assuming $paginatorData['items'] is an iterable of Program entities (e.g. Paginator or array)

        $itemsDTO = [];
        if (isset($paginatorData['items'])) {
            // If $paginatorData['items'] is a Doctrine Paginator
            if ($paginatorData['items'] instanceof Paginator) {
                foreach ($paginatorData['items']->getIterator() as $program) {
                    $itemsDTO[] = ProgramOutput::fromEntity($program);
                }
                $totalItems = $paginatorData['items']->count();
            } elseif (is_array($paginatorData['items'])) { // If it's an array of entities
                foreach ($paginatorData['items'] as $program) {
                    $itemsDTO[] = ProgramOutput::fromEntity($program);
                }
                $totalItems = $paginatorData['total'] ?? count($itemsDTO); // Use provided total or count
            } else { // Fallback or error
                $itemsDTO = [];
                $totalItems = $paginatorData['total'] ?? 0;
            }
        } else {
            $itemsDTO = [];
            $totalItems = 0;
        }


        return [
            'items' => $itemsDTO,
            'total' => $totalItems,
            'page' => $paginatorData['page'] ?? $page,
            'limit' => $paginatorData['limit'] ?? $limit,
            'pages' => ($limit > 0 && $totalItems > 0) ? (int)ceil($totalItems / $limit) : 1,
        ];
    }


    public function getProgramById(int $id): Program // Return entity
    {
        $program = $this->programRepository->find($id);
        if (!$program) {
            throw new EntityNotFoundException('Program not found with ID: ' . $id);
        }

        return $program;
    }
}
