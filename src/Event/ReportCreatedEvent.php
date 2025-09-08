<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Report;

class ReportCreatedEvent
{
    public function __construct(
        public readonly int $reportId,
        public readonly string $reportName,
        public readonly string $reportType,
        public readonly int $requestedById,
        public readonly array $parameters
    ) {
    }

    public static function fromEntity(Report $report): self
    {
        $requestedBy = $report->getRequestedBy();
        return new self(
            reportId: (int) $report->getId(),
            reportName: (string) $report->getName(),
            reportType: (string) $report->getType(),
            requestedById: $requestedBy ? (int) $requestedBy->getId() : 0,
            parameters: $report->getParameters() ?? []
        );
    }
} 