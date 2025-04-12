<?php

declare(strict_types=1);

namespace App\Factory\Application;

use App\DTO\Input\Application\ListApplicationsQueryDTO;
use Symfony\Component\HttpFoundation\Request;

class ListApplicationsQueryDTOFactory
{
    public function create(Request $request): ListApplicationsQueryDTO
    {
        $query = $request->query;
        $input = new ListApplicationsQueryDTO();

        $input->page = $query->has('page') ? (int) $query->get('page') : null;
        $input->limit = $query->has('limit') ? (int) $query->get('limit') : null;
        $input->status = $query->get('status');
        $input->sortBy = $query->get('sortBy');
        $input->sortDirection = $query->get('sortDirection');
        $input->programId = $query->has('programId') ? (int) $query->get('programId') : null;
        $input->applicantId = $query->has('applicantId') ? (int) $query->get('applicantId') : null;

        return $input;
    }
}
