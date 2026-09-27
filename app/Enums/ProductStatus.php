<?php

namespace App\Enums;

enum ProductStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::UNAVAILABLE => 'Unavailable',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::AVAILABLE => 'badge bg-success',
            self::UNAVAILABLE => 'badge bg-secondary',
        };
    }
}