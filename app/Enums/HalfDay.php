<?php

namespace App\Enums;

enum HalfDay: string
{
    case First = 'first';
    case Second = 'second';

    public function label(): string
    {
        return match ($this) {
            self::First => 'First half',
            self::Second => 'Second half',
        };
    }
}
