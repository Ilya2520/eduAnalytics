<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum ReportStatusEnum: string
{
    case completed = 'completed';
    case pending = 'pending';
    case cancelled = 'cancelled';
}
