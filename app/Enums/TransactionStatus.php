<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case ACTIVE = 'active';
    case VOIDED = 'voided';
    case CORRECTED = 'corrected';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::VOIDED => 'Voided',
            self::CORRECTED => 'Corrected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'badge bg-success',
            self::VOIDED => 'badge bg-danger',
            self::CORRECTED => 'badge bg-warning text-dark',
        };
    }
}