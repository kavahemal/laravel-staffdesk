<?php

namespace App\Enums;

enum LeaveStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /** Statuses that hold the dates and use up the balance. */
    public static function booked(): array
    {
        return [self::Pending, self::Approved];
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Colour for `<x-badge>`. */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'error',
            self::Cancelled => 'gray',
        };
    }
}
