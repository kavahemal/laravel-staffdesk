<?php

namespace App\Enums;

enum ExportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Done = 'done';
    case Failed = 'failed';

    /** Queued or being written – the user waits for it. */
    public static function running(): array
    {
        return [self::Pending, self::Processing];
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Colour for `<x-badge>`. */
    public function color(): string
    {
        return match ($this) {
            self::Pending, self::Processing => 'warning',
            self::Done => 'success',
            self::Failed => 'error',
        };
    }
}
