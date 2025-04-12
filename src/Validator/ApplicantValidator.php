<?php

declare(strict_types=1);

namespace App\Validator;

use App\DTO\Input\Applicant\CreateApplicantInputDTO;
use App\Repository\ApplicantRepository;

class ApplicantValidator
{
    public function __construct(
        private readonly ApplicantRepository $applicantRepository
    ) {
    }

    public function validateApplicantCreation(CreateApplicantInputDTO $dto): array
    {
        // Проверка уникальности email
        if ($this->applicantRepository->findOneBy(['email' => $dto->email])) {
            return ['success' => false, 'errors' => 'Email already exists'];
        }

        return ['success' => true];
    }
}
