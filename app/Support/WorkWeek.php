<?php

namespace App\Support;

use App\Enums\WorkDay;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** Which weekdays are full, half or off – the company's work week. */
final class WorkWeek
{
    /** Monday first, as shown on the Settings page. */
    public const DISPLAY_ORDER = [
        Carbon::MONDAY, Carbon::TUESDAY, Carbon::WEDNESDAY, Carbon::THURSDAY,
        Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY,
    ];

    /** @param  array<int, WorkDay>  $days  keyed by Carbon day-of-week (0 = Sunday) */
    public function __construct(private readonly array $days) {}

    /** @param  array<int|string, string>  $values  e.g. [1 => 'full', 6 => 'half', 0 => 'off']; missing days are off */
    public static function fromArray(array $values): self
    {
        $days = [];

        foreach (self::DISPLAY_ORDER as $day) {
            $days[$day] = WorkDay::tryFrom((string) ($values[$day] ?? '')) ?? WorkDay::Off;
        }

        return new self($days);
    }

    /** @return array<int, string> e.g. [1 => 'Monday', …, 0 => 'Sunday'] */
    public static function dayNames(): array
    {
        $names = Carbon::getDays();

        return array_combine(self::DISPLAY_ORDER, array_map(fn (int $day) => $names[$day], self::DISPLAY_ORDER));
    }

    public function day(int $dayOfWeek): WorkDay
    {
        return $this->days[$dayOfWeek] ?? WorkDay::Off;
    }

    /** @return list<int> Carbon day-of-week numbers that are off (same numbering as JS `getDay()`) */
    public function offDays(): array
    {
        return array_keys(array_filter($this->days, fn (WorkDay $day) => $day === WorkDay::Off));
    }

    /** @return list<int> */
    public function halfDays(): array
    {
        return array_keys(array_filter($this->days, fn (WorkDay $day) => $day === WorkDay::Half));
    }

    public function weight(CarbonInterface $date): float
    {
        return $this->day($date->dayOfWeek)->weight();
    }

    /** @return array<int, string> keyed by Carbon day-of-week */
    public function toArray(): array
    {
        return array_map(fn (WorkDay $day) => $day->value, $this->days);
    }
}
