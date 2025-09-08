<?php

declare(strict_types=1);

namespace App\Factory\Program;

use App\DTO\Input\Program\ListProgramsQueryDTO;
use Symfony\Component\HttpFoundation\Request;

class ListProgramsQueryDTOFactory
{
    public function __construct(
        private readonly int $defaultLimit,
        private readonly int $maxLimit,
    ) {
    }

    public function create(Request $request): ListProgramsQueryDTO
    {
        $query = $request->query;
        $dto = new ListProgramsQueryDTO();

        $dto->page = $query->has('page') ? (int) $query->get('page') : $dto->page;
        $requestedLimit = $query->has('limit') ? (int) $query->get('limit') : $this->defaultLimit;
        if ($requestedLimit < 1) {
            $requestedLimit = 1;
        }
        if ($requestedLimit > $this->maxLimit) {
            $requestedLimit = $this->maxLimit;
        }
        $dto->limit = $requestedLimit;

        $isActive = $query->get('isActive');
        if ($isActive !== null) {
            $dto->isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        $dto->degree = $query->get('degree');

        if ($dto->page < 1) {
            $dto->page = 1;
        }

        return $dto;
    }
} 