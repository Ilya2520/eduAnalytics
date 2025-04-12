<?php

namespace App\Service;

use App\DTO\Input\Application\ListApplicationsQueryDTO;
use App\DTO\Output\Application\ApplicationListOutputDTO;
use App\DTO\Output\Application\ApplicationOutputDTO;
use App\Entity\Application;
use App\Factory\Application\ApplicationOutputDTOFactory;
use App\Repository\ApplicantRepository;
use App\Repository\ApplicationRepository;
use App\Repository\ProgramRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApplicationService
{
    public function __construct(
        private readonly ApplicationRepository $repository,
        private readonly ApplicantRepository $applicantRepository,
        private readonly ProgramRepository $programRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ApplicationOutputDTOFactory $outputDTOFactory
    ) {
    }

    public function createApplication(
        int $applicantId,
        int $programId,
        ?array $documents = null,
        ?string $notes = null
    ): ApplicationOutputDTO {
        $application = new Application();

        $applicant = $this->applicantRepository->find($applicantId);

        if ($applicant === null) {
            throw new \DomainException('Applicant not found');
        }

        $program = $this->programRepository->find($programId);

        if ($program === null) {
            throw new \DomainException('Program not found');
        }

        $application->setApplicant($applicant);
        $application->setProgram($program);

        $application->setStatus('draft');
        $application->setDocuments($documents);
        $application->setNotes($notes);

        $this->entityManager->persist($application);
        $this->entityManager->flush();

        return $this->outputDTOFactory->create($application);
    }

    public function getApplicationById(int $id): ApplicationOutputDTO
    {
        $application = $this->repository->find($id);
        if (!$application) {
            throw new \InvalidArgumentException('Application not found');
        }

        return $this->outputDTOFactory->create($application);
    }

    public function listApplications(ListApplicationsQueryDTO $query): ApplicationListOutputDTO
    {
        $paginator = $this->repository->findByCriteria(
            status: $query->status,
            applicantId: $query->applicantId,
            programId: $query->programId,
            sortBy: $query->sortBy,
            sortDirection: $query->sortDirection,
            page: $query->page,
            limit: $query->limit
        );

        $outputDTO = new ApplicationListOutputDTO();
        $outputDTO->page = $query->page;
        $outputDTO->limit = $query->limit;
        $outputDTO->total = $paginator->count();
        $outputDTO->items = array_map(
            fn (Application $app) => $this->outputDTOFactory->create($app),
            iterator_to_array($paginator->getIterator())
        );

        return $outputDTO;
    }
}
