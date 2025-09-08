<?php

declare(strict_types=1);

namespace App\Factory\Report;

use App\DTO\Input\Report\ListReportsQueryDTO;
use Symfony\Component\HttpFoundation\Request;

class ListReportsQueryDTOFactory
{
    public function __construct(
        private readonly int $defaultLimit,
        private readonly int $maxLimit,
    ) {
    }

    public function create(Request $request): ListReportsQueryDTO
    {
        $query = $request->query;
        $dto = new ListReportsQueryDTO();

        $dto->page = $query->has('page') ? (int) $query->get('page') : $dto->page;
        $requestedLimit = $query->has('limit') ? (int) $query->get('limit') : $this->defaultLimit;
        if ($requestedLimit < 1) {
            $requestedLimit = 1;
        }
        if ($requestedLimit > $this->maxLimit) {
            $requestedLimit = $this->maxLimit;
        }
        $dto->limit = $requestedLimit;

        $dto->type = $query->get('type');
        $dto->status = $query->get('status');

        if ($dto->page < 1) {
            $dto->page = 1;
        }

        return $dto;
    }
} 