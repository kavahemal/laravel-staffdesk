<?php

namespace App\Enums;

enum WorkDay: string
{
    case Full = 'full';
    case Half = 'half';
    case Off = 'off';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Full day',
            self::Half => 'Half day',
            self::Off => 'Off',
        };
    }

    /** How much one leave day on this weekday counts. */
    public function weight(): float
    {
        return match ($this) {
            self::Full => 1.0,
            self::Half => 0.5,
            self::Off => 0.0,
        };
    }
}
