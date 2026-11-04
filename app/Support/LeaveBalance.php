<?php

namespace App\Support;

use App\Models\LeaveType;

/** One leave type's balance for a user in one year. Pending leave counts as used, same as when applying. */
final class LeaveBalance
{
    public function __construct(
        public readonly LeaveType $type,
        public readonly float $approved,
        public readonly float $pending,
    ) {}

    public function quota(): ?int
    {
        return $this->type->yearly_quota;
    }

    public function used(): float
    {
        return $this->approved + $this->pending;
    }

    /** Null = unlimited. Never below 0, even if the quota was lowered after leave was booked. */
    public function remaining(): ?float
    {
        return $this->quota() === null ? null : max(0.0, $this->quota() - $this->used());
    }

    /** 0–100, for the progress bar; null when unlimited. */
    public function usedPercent(): ?int
    {
        if (! $this->quota()) {
            return null;
        }

        return (int) min(100, round($this->used() / $this->quota() * 100));
    }
}
