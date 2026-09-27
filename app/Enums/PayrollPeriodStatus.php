<?php

namespace App\Enums;

enum PayrollPeriodStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case FINALIZED = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::CLOSED => 'Closed',
            self::FINALIZED => 'Finalized',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::OPEN => 'badge bg-success',
            self::CLOSED => 'badge bg-warning text-dark',
            self::FINALIZED => 'badge bg-info',
        };
    }
}