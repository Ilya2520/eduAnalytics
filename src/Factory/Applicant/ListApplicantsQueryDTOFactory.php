<?php

declare(strict_types=1);

namespace App\Factory\Applicant;

use App\DTO\Input\Applicant\ListApplicantsQueryDTO;
use Symfony\Component\HttpFoundation\Request;

class ListApplicantsQueryDTOFactory
{
    public function __construct(
        private readonly int $defaultLimit,
        private readonly int $maxLimit,
    ) {
    }

    public function create(Request $request): ListApplicantsQueryDTO
    {
        $query = $request->query;
        $input = new ListApplicantsQueryDTO();

        $input->page = $query->has('page') ? (int) $query->get('page') : $input->page;
        $requestedLimit = $query->has('limit') ? (int) $query->get('limit') : $this->defaultLimit;
        if ($requestedLimit < 1) {
            $requestedLimit = 1;
        }
        if ($requestedLimit > $this->maxLimit) {
            $requestedLimit = $this->maxLimit;
        }
        $input->limit = $requestedLimit;

        $input->search = $query->get('search');
        $input->sortBy = $query->get('sortBy');
        $input->sortDirection = $query->get('sortDirection');

        if ($input->page < 1) {
            $input->page = 1;
        }

        return $input;
    }
}
