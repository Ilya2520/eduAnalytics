<?php

declare(strict_types=1);

namespace App\Validator;

use App\DTO\Input\Application\CreateApplicationInputDTO;
use App\Repository\ApplicantRepository;
use App\Repository\ProgramRepository;

class ApplicationValidator
{
    public function __construct(
        private readonly ApplicantRepository $applicantRepository,
        private readonly ProgramRepository $programRepository
    ) {
    }

    public function validateApplicationCreation(CreateApplicationInputDTO $dto): array
    {
        // Проверка существования абитуриента
        $applicant = $this->applicantRepository->find($dto->applicantId);
        if (!$applicant) {
            return ['success' => false, 'errors' => 'Applicant not found'];
        }

        // Проверка существования программы
        $program = $this->programRepository->find($dto->programId);
        if (!$program) {
            return ['success' => false, 'errors' => 'Program not found'];
        }

        // Проверка активности программы
        if (!$program->isActive()) {
            return ['success' => false, 'errors' => 'Program is not active'];
        }

        return ['success' => true];
    }
}
