<?php

declare(strict_types=1);

namespace App\Factory\Applicant;

use App\DTO\Output\Applicant\ApplicantOutputDTO;
use App\Entity\Applicant;

class ApplicantOutputDTOFactory
{
    public function create(Applicant $applicant): ApplicantOutputDTO
    {
        $dto = new ApplicantOutputDTO();
        $dto->id = $applicant->getId();
        $dto->firstName = $applicant->getFirstName();
        $dto->lastName = $applicant->getLastName();
        $dto->email = $applicant->getEmail();
        $dto->phone = $applicant->getPhone();
        $dto->birthDate = $applicant->getBirthDate()?->format('Y-m-d');
        $dto->createdAt = $applicant->getCreatedAt();

        return $dto;
    }
}
