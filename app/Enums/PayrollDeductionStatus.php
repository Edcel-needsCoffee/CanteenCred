<?php

namespace App\Enums;

enum PayrollDeductionStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PROCESSED = 'processed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::PROCESSED => 'Processed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'badge bg-secondary',
            self::APPROVED => 'badge bg-success',
            self::REJECTED => 'badge bg-danger',
            self::PROCESSED => 'badge bg-primary',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    public function isVisibleToPayroll(): bool
    {
        return in_array($this, [self::APPROVED, self::PROCESSED]);
    }
}