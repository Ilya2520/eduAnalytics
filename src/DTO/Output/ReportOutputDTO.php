<?php

declare(strict_types=1);

namespace App\DTO\Output;

use App\Entity\Report;
use DateTimeInterface;

class ReportOutputDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
        public array $parameters,
        public string $status,
        public ?string $filePath,
        public DateTimeInterface $createdAt,
        public ?DateTimeInterface $completedAt,
        public string $requestedBy,
        public ?string $downloadUrl = null
    ) {
    }

    public static function createFromEntity(Report $report): self
    {
        return new self(
            $report->getId(),
            $report->getName(),
            $report->getType(),
            $report->getParameters(),
            $report->getStatus(),
            $report->getFilePath(),
            $report->getCreatedAt(),
            $report->getCompletedAt(),
            $report->getRequestedBy()->getLastName(),
            $report->getFilePath() ? '/api/reports/'.$report->getId().'/download' : null
        );
    }
}
