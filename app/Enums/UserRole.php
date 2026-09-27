<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMINISTRATOR = 'administrator';
    case CASHIER = 'cashier';
    case PAYROLL = 'payroll';
    case FACULTY = 'faculty';

    public function label(): string
    {
        return match ($this) {
            self::ADMINISTRATOR => 'Canteen Administrator',
            self::CASHIER => 'Canteen Staff / Cashier',
            self::PAYROLL => 'Payroll / Finance Officer',
            self::FACULTY => 'Faculty',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}