<?php

namespace App\Services;

use App\Enums\HalfDay;
use App\Enums\LeaveStatus;
use App\Enums\WorkDay;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveCancelled;
use App\Notifications\LeaveRecorded;
use App\Notifications\LeaveRequested;
use App\Notifications\LeaveStatusChanged;
use App\Support\LeaveBalance;
use App\Support\WorkWeek;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class LeaveService
{
    public function __construct(private readonly WorkWeek $workWeek) {}

    /**
     * Creates a pending request after checking the leave rules.
     *
     * @param  array{leave_type_id: int|string, from_date: string, to_date: string, half_day?: ?string, reason: string}  $data
     *
     * `$recordedBy` = HR/Admin entering leave for the employee: past dates of this year are allowed
     * and the leave is approved right away. All other rules stay the same.
     *
     * @throws ValidationException
     */
    public function apply(User $user, array $data, ?User $recordedBy = null): LeaveRequest
    {
        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->startOfDay();
        $halfDay = HalfDay::tryFrom((string) ($data['half_day'] ?? ''));

        if ($recordedBy && $from->year !== Carbon::today()->year) {
            $this->fail('from_date', 'Recorded leave must be in the current year.');
        }
        if (! $recordedBy && $from->lt(Carbon::today())) {
            $this->fail('from_date', 'The start date can not be in the past.');
        }
        if ($to->lt($from)) {
            $this->fail('to_date', 'The end date must be on or after the start date.');
        }
        if ($to->year !== $from->year) {
            $this->fail('to_date', 'Leave must start and end in the same year. Apply for each year separately.');
        }
        if ($halfDay && ! $to->equalTo($from)) {
            $this->fail('half_day', 'A half-day leave must start and end on the same day.');
        }
        if ($halfDay && $this->workWeek->day($from->dayOfWeek) !== WorkDay::Full) {
            $this->fail('half_day', 'Half-day leave is only possible on a full working day.');
        }

        $days = $halfDay ? 0.5 : $this->countDays($from, $to);

        if ($days <= 0) {
            $this->fail('to_date', 'The selected dates are all non-working days.');
        }

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);

        return DB::transaction(function () use ($user, $leaveType, $from, $to, $halfDay, $days, $data, $recordedBy) {
            // Lock the applicant so two parallel requests can't both pass the overlap and balance checks.
            User::whereKey($user->id)->lockForUpdate()->first();

            $clash = $user->leaveRequests()->booked()->overlapping($from, $to)
                // A first-half and a second-half leave on the same day can both be taken.
                ->when($halfDay, fn ($query) => $query->where(
                    fn ($query) => $query->whereNull('half_day')->orWhere('half_day', $halfDay)
                ))
                ->exists();

            if ($clash) {
                $this->fail('from_date', ($recordedBy ? 'The employee already has' : 'You already have').' pending or approved leave on some of these days.');
            }

            $remaining = $this->remaining($user, $leaveType, $from->year);

            if ($remaining !== null && $days > $remaining) {
                $this->fail('leave_type_id', 'Only '.format_days($remaining)." day(s) of {$leaveType->name} left for {$from->year}, this request needs ".format_days($days).'.');
            }

            $leave = $user->leaveRequests()->create([
                'leave_type_id' => $leaveType->id,
                'from_date' => $from,
                'to_date' => $to,
                'half_day' => $halfDay,
                'days' => $days,
                'reason' => $data['reason'],
                ...($recordedBy ? [
                    'status' => LeaveStatus::Approved,
                    'actioned_by' => $recordedBy->id,
                    'actioned_at' => now(),
                ] : []),
            ])->setRelation('user', $user)->setRelation('leaveType', $leaveType);

            if ($recordedBy) {
                $user->notify(new LeaveRecorded($leave->setRelation('actionedBy', $recordedBy)));
            } else {
                Notification::send($this->approversFor($leave), new LeaveRequested($leave));
            }

            return $leave;
        });
    }

    /**
     * Active users who may approve the leave: approve-any holders (HR, Admin) and the
     * applicant's department manager – never the applicant.
     *
     * @return EloquentCollection<int, User>
     */
    public function approversFor(LeaveRequest $leave): EloquentCollection
    {
        $leave->loadMissing('user.employee.department.manager');
        $manager = $leave->user->employee?->department?->manager;

        return User::permission('leaves.approve-any')->active()->get()
            ->when($manager?->is_active, fn (EloquentCollection $users) => $users->push($manager))
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $leave->user_id)
            ->values();
    }

    /**
     * Approve or reject a pending request. False when it is no longer pending
     * (cancelled or decided by someone else in the meantime).
     */
    public function decide(LeaveRequest $leave, User $by, LeaveStatus $decision, ?string $comment): bool
    {
        if (! in_array($decision, [LeaveStatus::Approved, LeaveStatus::Rejected], true)) {
            throw new InvalidArgumentException("A leave can only be approved or rejected, not {$decision->value}.");
        }

        return DB::transaction(function () use ($leave, $by, $decision, $comment) {
            $values = [
                'status' => $decision,
                'actioned_by' => $by->id,
                'actioned_at' => now(),
                'comment' => filled($comment) ? $comment : null,
            ];

            // Conditional update, so two approvers (or a cancel) at the same moment can't both win.
            $updated = LeaveRequest::whereKey($leave->id)->where('status', LeaveStatus::Pending)->update($values);

            if ($updated === 0) {
                return false;
            }

            $leave->forceFill($values)->syncOriginal();
            $leave->setRelation('actionedBy', $by);
            $leave->loadMissing('user')->user->notify(new LeaveStatusChanged($leave));

            return true;
        });
    }

    /**
     * Cancel pending leave, or approved leave that hasn't started (the approvers are told, the days return
     * to the balance). False when it can no longer be cancelled, e.g. rejected or started in the meantime.
     */
    public function cancel(LeaveRequest $leave): bool
    {
        return DB::transaction(function () use ($leave) {
            // Locked, so a cancel and an approval at the same moment can't both win.
            $current = LeaveRequest::whereKey($leave->id)->lockForUpdate()->first();

            if (! $current?->isCancellable()) {
                return false;
            }

            $wasApproved = $current->status === LeaveStatus::Approved;
            $current->update(['status' => LeaveStatus::Cancelled]);

            $leave->status = LeaveStatus::Cancelled;
            $leave->syncOriginalAttribute('status');

            if ($wasApproved) {
                Notification::send($this->approversFor($leave), new LeaveCancelled($leave));
            }

            return true;
        });
    }

    /**
     * Balance per leave type in the year: every active type, plus inactive ones the user still has leave on.
     *
     * @return Collection<int, LeaveBalance> keyed by leave type id, ordered by name
     */
    public function balances(User $user, int $year): Collection
    {
        $booked = $user->leaveRequests()->booked()->inYear($year)
            ->groupBy('leave_type_id', 'status')
            ->selectRaw('leave_type_id, status, SUM(days) as days')
            ->toBase()
            ->get();

        $sum = fn (int $typeId, LeaveStatus $status) => (float) $booked
            ->where('leave_type_id', $typeId)->where('status', $status->value)->sum('days');

        return LeaveType::where('is_active', true)
            ->orWhereIn('id', $booked->pluck('leave_type_id'))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (LeaveType $type) => [$type->id => new LeaveBalance(
                $type,
                approved: $sum($type->id, LeaveStatus::Approved),
                pending: $sum($type->id, LeaveStatus::Pending),
            )]);
    }

    /**
     * For the pending requests on the "Leave requests" page: days left of that type (the request itself
     * counts as used, as everywhere) and who else in the employee's department is already off on those days.
     * Two queries for the whole page.
     *
     * @param  Collection<int, LeaveRequest>  $leaves  with user.employee and leaveType loaded
     * @return array<int, array{remaining: ?float, othersOff: Collection<int, string>}> keyed by leave id
     */
    public function approvalContext(Collection $leaves): array
    {
        $pending = $leaves->where('status', LeaveStatus::Pending);

        if ($pending->isEmpty()) {
            return [];
        }

        $booked = LeaveRequest::booked()
            ->whereIn('user_id', $pending->pluck('user_id')->unique())
            ->where(fn ($query) => $pending->map(fn (LeaveRequest $leave) => $leave->from_date->year)->unique()
                ->each(fn (int $year) => $query->orWhere(fn ($query) => $query->inYear($year))))
            ->get(['user_id', 'leave_type_id', 'from_date', 'days', 'status']);

        $departmentIds = $pending->map(fn (LeaveRequest $leave) => $leave->user->employee?->department_id)->filter()->unique();

        $off = LeaveRequest::where('status', LeaveStatus::Approved)
            ->overlapping($pending->min('from_date'), $pending->max('to_date'))
            ->whereHas('user.employee', fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->with('user.employee')
            ->orderBy('from_date')
            ->get();

        return $pending->mapWithKeys(function (LeaveRequest $leave) use ($booked, $off) {
            $own = $booked->where('user_id', $leave->user_id)->where('leave_type_id', $leave->leave_type_id)
                ->filter(fn (LeaveRequest $other) => $other->from_date->year === $leave->from_date->year);

            $balance = new LeaveBalance(
                $leave->leaveType,
                approved: (float) $own->where('status', LeaveStatus::Approved)->sum('days'),
                pending: (float) $own->where('status', LeaveStatus::Pending)->sum('days'),
            );

            $othersOff = $off->filter(fn (LeaveRequest $other) => $other->user_id !== $leave->user_id
                && $other->user->employee?->department_id === $leave->user->employee?->department_id
                && $other->from_date->lte($leave->to_date) && $other->to_date->gte($leave->from_date))
                ->map(fn (LeaveRequest $other) => $other->user->name)
                ->unique()
                ->values();

            return [$leave->id => ['remaining' => $balance->remaining(), 'othersOff' => $othersOff]];
        })->all();
    }

    /** Days left of the type's yearly quota (pending + approved count as used); null = unlimited. */
    public function remaining(User $user, LeaveType $leaveType, int $year): ?float
    {
        // An inactive type without leave this year is not in balances() – nothing used yet.
        return ($this->balances($user, $year)->get($leaveType->id) ?? new LeaveBalance($leaveType, approved: 0, pending: 0))->remaining();
    }

    /** Leave days from `$from` to `$to` inclusive: full working days count 1, half days 0.5, off days 0. */
    public function countDays(CarbonInterface $from, CarbonInterface $to): float
    {
        $last = Carbon::instance($to)->startOfDay();
        $days = 0.0;

        for ($day = Carbon::instance($from)->startOfDay(); $day->lte($last); $day->addDay()) {
            $days += $this->workWeek->weight($day);
        }

        return $days;
    }

    /** @throws ValidationException */
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
