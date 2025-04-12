<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Output\Applicant\ApplicantListOutputDTO;
use App\DTO\Output\Applicant\ApplicantOutputDTO;
use App\Entity\Applicant;
use App\Factory\Applicant\ApplicantOutputDTOFactory;
use App\Repository\ApplicantRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApplicantService
{
    public function __construct(
        private readonly ApplicantRepository $applicantRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ApplicantOutputDTOFactory $outputDTOFactory
    ) {
    }

    public function createApplicant(
        string $firstName,
        string $lastName,
        string $email,
        ?string $phone = null,
        ?\DateTimeInterface $birthDate = null
    ): ApplicantOutputDTO {
        $applicant = new Applicant();
        $applicant->setFirstName($firstName);
        $applicant->setLastName($lastName);
        $applicant->setEmail($email);
        $applicant->setPhone($phone);
        $applicant->setBirthDate($birthDate);

        $this->entityManager->persist($applicant);
        $this->entityManager->flush();

        return $this->outputDTOFactory->create($applicant);
    }

    public function listApplicants(
        int $page = 1,
        int $limit = 10,
        ?string $search = null,
        ?string $sortBy = 'createdAt',
        ?string $sortDirection = 'desc'
    ): ApplicantListOutputDTO {
        $paginator = $this->applicantRepository->findByCriteria(
            $search,
            $sortBy,
            $sortDirection,
            $page,
            $limit
        );

        $outputDTO = new ApplicantListOutputDTO();
        $outputDTO->page = $page;
        $outputDTO->limit = $limit;
        $outputDTO->total = $paginator->count();
        $outputDTO->items = array_map(
            fn (Applicant $applicant) => $this->outputDTOFactory->create($applicant),
            iterator_to_array($paginator->getIterator())
        );

        return $outputDTO;
    }
}
