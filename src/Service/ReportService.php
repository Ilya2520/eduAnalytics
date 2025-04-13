<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Output\ReportOutputDTO;
use App\Entity\Report;
use App\Entity\User;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class ReportService
{
    public function __construct(
        private readonly ReportRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly Security $security
    ) {
    }

    public function getReportsList(
        int $page,
        int $limit,
        ?string $type = null,
        ?string $status = null
    ): array {
        try {
            $paginator = $this->repository->findByFilters($page, $limit, $type, $status);

            $items = [];
            foreach ($paginator as $report) {
                $items[] = ReportOutputDTO::createFromEntity($report);
            }

            return [
                'items' => $items,
                'total' => count($paginator),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil(count($paginator) / $limit)
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error fetching reports', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Could not fetch reports');
        }
    }

    public function getReportById(int $id): ?Report
    {
        try {
            return $this->repository->find($id);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching report', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Could not fetch report');
        }
    }

    public function createReport(array $data): Report
    {
        $this->validateReportData($data);

        /** @var User $user */
        $user = $this->security->getUser();

        try {
            $report = new Report();
            $report->setName($data['name']);
            $report->setType($data['type']);
            $report->setParameters($data['parameters'] ?? []);
            $report->setRequestedBy($user);

            $this->em->persist($report);
            $this->em->flush();

            return $report;
        } catch (\Exception $e) {
            $this->logger->error('Error creating report', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Could not create report');
        }
    }

    public function updateReport(int $id, array $data): Report
    {
        $report = $this->getReportById($id);
        if (!$report) {
            throw new \RuntimeException('Report not found');
        }

        $this->validateReportData($data, true);

        try {
            if (isset($data['name'])) {
                $report->setName($data['name']);
            }
            if (isset($data['type'])) {
                $report->setType($data['type']);
            }
            if (isset($data['parameters'])) {
                $report->setParameters($data['parameters']);
            }
            if (isset($data['status'])) {
                $report->setStatus($data['status']);
            }
            if (isset($data['filePath'])) {
                $report->setFilePath($data['filePath']);
            }
            if (isset($data['completedAt'])) {
                $report->setCompletedAt(new \DateTime($data['completedAt']));
            }

            $this->em->flush();

            return $report;
        } catch (\Exception $e) {
            $this->logger->error('Error updating report', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Could not update report');
        }
    }

    public function deleteReport(int $id): void
    {
        $report = $this->getReportById($id);
        if (!$report) {
            throw new \RuntimeException('Report not found');
        }

        try {
            // Удаляем файл отчета, если он существует
            if ($report->getFilePath() && file_exists($report->getFilePath())) {
                unlink($report->getFilePath());
            }

            $this->em->remove($report);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error('Error deleting report', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Could not delete report');
        }
    }

    private function validateReportData(array $data, bool $isUpdate = false): void
    {
        $requiredFields = ['name', 'type'];

        foreach ($requiredFields as $field) {
            if (!$isUpdate && !array_key_exists($field, $data)) {
                throw new \InvalidArgumentException("Field $field is required");
            }
        }

        if (isset($data['status']) && !in_array($data['status'], ['pending', 'processing', 'completed', 'failed'])) {
            throw new \InvalidArgumentException('Invalid status value');
        }

        if (isset($data['parameters']) && !is_array($data['parameters'])) {
            throw new \InvalidArgumentException('Parameters must be an array');
        }
    }
}
