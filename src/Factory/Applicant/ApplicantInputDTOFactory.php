<?php

declare(strict_types=1);

namespace App\Factory\Applicant;

use App\DTO\Input\Applicant\CreateApplicantInputDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class ApplicantInputDTOFactory
{
    public function __construct(
        private readonly SerializerInterface $serializer
    ) {
    }

    public function createFromRequest(Request $request): CreateApplicantInputDTO
    {
        return $this->serializer->deserialize(
            $request->getContent(),
            CreateApplicantInputDTO::class,
            'json'
        );
    }
}
