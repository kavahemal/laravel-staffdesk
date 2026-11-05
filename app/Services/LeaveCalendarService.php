<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Enums\WorkDay;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\WorkWeek;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Month grid for the "Leave calendar": pending and approved leave per day, Monday first. */
class LeaveCalendarService
{
    public function __construct(private readonly WorkWeek $workWeek) {}

    /** `?month=2026-11` as the first day of that month; anything else is the current month. */
    public function parseMonth(mixed $value): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return Carbon::createFromFormat('!Y-m', $value)->startOfMonth();
        }

        return Carbon::today()->startOfMonth();
    }

    /**
     * Whole weeks around the month (one query). Off days get no leave: they aren't counted as leave either.
     *
     * @return list<list<array{date: Carbon, inMonth: bool, today: bool, workDay: WorkDay, leaves: Collection<int, LeaveRequest>}>>
     */
    public function weeks(User $user, Carbon $month, ?int $departmentId = null): array
    {
        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->startOfDay();

        $leaves = LeaveRequest::booked()
            ->onCalendarOf($user)
            ->overlapping($start, $end)
            ->when($departmentId, fn ($query) => $query->inDepartment($departmentId))
            ->with(['user.roles', 'user.employee.department', 'leaveType'])
            ->orderBy('from_date')
            ->orderBy('leave_requests.id')
            ->get();

        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $workDay = $this->workWeek->day($date->dayOfWeek);

            $days[] = [
                'date' => $date->copy(),
                'inMonth' => $date->month === $month->month,
                'today' => $date->isToday(),
                'workDay' => $workDay,
                'leaves' => $workDay === WorkDay::Off
                    ? collect()
                    : $leaves->filter(fn (LeaveRequest $leave) => $leave->from_date->lte($date) && $leave->to_date->gte($date))->values(),
            ];
        }

        return array_chunk($days, 7);
    }

    /**
     * What the day popup lists, keyed by Y-m-d: who is off with code, department and role – never the reason.
     *
     * @param  list<list<array{date: Carbon, leaves: Collection<int, LeaveRequest>}>>  $weeks  from weeks()
     * @return array<string, array{title: string, leaves: list<array{name: string, details: string, type: string, period: string, status: string, pending: bool}>}>
     */
    public function dayDetails(array $weeks): array
    {
        return collect($weeks)->flatten(1)
            ->filter(fn (array $day) => $day['leaves']->isNotEmpty())
            ->mapWithKeys(fn (array $day) => [$day['date']->toDateString() => [
                'title' => $day['date']->format('l').', '.format_date($day['date']),
                'leaves' => $day['leaves']->map(fn (LeaveRequest $leave) => [
                    'name' => $leave->user->name,
                    'details' => collect([
                        $leave->user->employee?->employee_code,
                        $leave->user->employee?->department?->name,
                        $leave->user->roles->first()?->name,
                    ])->filter()->join(' · '),
                    'type' => $leave->leaveType->name.($leave->half_day ? ', '.strtolower($leave->half_day->label()) : ''),
                    'period' => $leave->period(),
                    'status' => $leave->status->label(),
                    'pending' => $leave->status === LeaveStatus::Pending,
                ])->values()->all(),
            ]])
            ->all();
    }
}
