<?php

declare(strict_types=1);

namespace App\Factory\Application;

use App\DTO\Output\Application\ApplicationOutputDTO;
use App\Entity\Application;

class ApplicationOutputDTOFactory
{
    public function create(Application $application): ApplicationOutputDTO
    {
        $dto = new ApplicationOutputDTO();
        $dto->id = $application->getId();
        $dto->applicantId = $application->getApplicant()->getId();
        $dto->programId = $application->getProgram()->getId();
        $dto->status = $application->getStatus();
        $dto->createdAt = $application->getCreatedAt();
        $dto->updatedAt = $application->getUpdatedAt();

        return $dto;
    }
}
