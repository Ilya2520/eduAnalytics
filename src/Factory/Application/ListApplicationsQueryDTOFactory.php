<?php

declare(strict_types=1);

namespace App\Factory\Application;

use App\DTO\Input\Application\ListApplicationsQueryDTO;
use Symfony\Component\HttpFoundation\Request;

class ListApplicationsQueryDTOFactory
{
    public function __construct(
        private readonly int $defaultLimit,
        private readonly int $maxLimit,
    ) {
    }

    public function create(Request $request): ListApplicationsQueryDTO
    {
        $query = $request->query;
        $input = new ListApplicationsQueryDTO();

        $input->page = $query->has('page') ? (int) $query->get('page') : $input->page;
        $requestedLimit = $query->has('limit') ? (int) $query->get('limit') : $this->defaultLimit;
        if ($requestedLimit < 1) {
            $requestedLimit = 1;
        }
        if ($requestedLimit > $this->maxLimit) {
            $requestedLimit = $this->maxLimit;
        }
        $input->limit = $requestedLimit;

        $input->status = $query->get('status');
        $input->sortBy = $query->get('sortBy');
        $input->sortDirection = $query->get('sortDirection');
        $input->programId = $query->has('programId') ? (int) $query->get('programId') : null;
        $input->applicantId = $query->has('applicantId') ? (int) $query->get('applicantId') : null;

        if ($input->page < 1) {
            $input->page = 1;
        }

        return $input;
    }
}
