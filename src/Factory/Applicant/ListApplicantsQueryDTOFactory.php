<?php

declare(strict_types=1);

namespace App\Factory\Applicant;

use App\DTO\Input\Applicant\ListApplicantsQueryDTO;
use Symfony\Component\HttpFoundation\Request;

class ListApplicantsQueryDTOFactory
{
    public function create(Request $request): ListApplicantsQueryDTO
    {
        $query = $request->query;
        $input = new ListApplicantsQueryDTO();

        $input->page = $query->has('page') ? (int) $query->get('page') : null;
        $input->limit = $query->has('limit') ? (int) $query->get('limit') : null;
        $input->search = $query->get('search');
        $input->sortBy = $query->get('sortBy');
        $input->sortDirection = $query->get('sortDirection');

        return $input;
    }
}
