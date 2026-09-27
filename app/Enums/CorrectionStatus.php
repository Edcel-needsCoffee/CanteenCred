<?php

namespace App\Enums;

enum CorrectionStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'badge bg-warning text-dark',
            self::APPROVED => 'badge bg-success',
            self::REJECTED => 'badge bg-danger',
        };
    }
}