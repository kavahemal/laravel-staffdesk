<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /** Next week's Monday–Tuesday: always in the future and never on a week-off day. */
    public function definition(): array
    {
        $from = Carbon::today()->next(Carbon::MONDAY);

        return [
            'user_id' => User::factory(),
            'leave_type_id' => LeaveType::factory(),
            'from_date' => $from,
            'to_date' => $from->copy()->addDay(),
            'days' => 2,
            'reason' => fake()->sentence(),
            'status' => LeaveStatus::Pending,
        ];
    }

    public function approved(?User $by = null): static
    {
        return $this->actioned(LeaveStatus::Approved, $by);
    }

    public function rejected(?User $by = null): static
    {
        return $this->actioned(LeaveStatus::Rejected, $by);
    }

    /** Fixed dates; `$days` is given, not counted, so tests stay independent of the work week. */
    public function on(string $from, ?string $to = null, float $days = 1): static
    {
        return $this->state(fn () => ['from_date' => $from, 'to_date' => $to ?? $from, 'days' => $days]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => LeaveStatus::Cancelled]);
    }

    private function actioned(LeaveStatus $status, ?User $by): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'actioned_by' => $by ?? User::factory(),
            'actioned_at' => now(),
        ]);
    }
}
