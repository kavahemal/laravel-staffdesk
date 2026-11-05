<?php

namespace App\Models;

use App\Enums\HalfDay;
use App\Enums\LeaveStatus;
use App\Policies\LeavePolicy;
use Carbon\CarbonInterface;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[UsePolicy(LeavePolicy::class)]
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'from_date',
        'to_date',
        'half_day',
        'days',
        'reason',
        'status',
        'actioned_by',
        'actioned_at',
        'comment',
    ];

    protected $attributes = [
        'status' => LeaveStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'half_day' => HalfDay::class,
            'days' => 'float',
            'status' => LeaveStatus::class,
            'actioned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /** HR/Admin or manager who approved or rejected the request. */
    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    /** "09 Nov 2026", "09 Nov 2026 – 12 Nov 2026" or "09 Nov 2026, first half" – for lists and messages. */
    public function period(): string
    {
        $period = $this->from_date->equalTo($this->to_date)
            ? format_date($this->from_date)
            : format_date($this->from_date).' – '.format_date($this->to_date);

        return $this->half_day ? $period.', '.strtolower($this->half_day->label()) : $period;
    }

    /** The owner may still cancel it: pending, or approved and not started yet. */
    public function isCancellable(): bool
    {
        return $this->status === LeaveStatus::Pending
            || ($this->status === LeaveStatus::Approved && $this->from_date->isAfter(Carbon::today()));
    }

    /** Pending or approved: holds its dates and counts against the balance. */
    public function scopeBooked(Builder $query): void
    {
        $query->whereIn('status', LeaveStatus::booked());
    }

    /**
     * Other people's leave an approver sees on "Leave requests": everyone's for approve-any (HR/Admin),
     * the own department's for approve-team (manager). The list version of LeavePolicy::approve().
     */
    public function scopeVisibleToApprover(Builder $query, User $user): void
    {
        $query->whereNot('user_id', $user->id);

        if ($user->can('leaves.approve-any')) {
            return;
        }

        if ($user->can('leaves.approve-team')) {
            $query->whereHas('user.employee.department', fn (Builder $department) => $department->where('manager_id', $user->id));

            return;
        }

        $query->whereRaw('1 = 0');
    }

    /** Shares at least one day with the from-to range (pass start-of-day dates). */
    public function scopeOverlapping(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->where('from_date', '<=', $to)->where('to_date', '>=', $from);
    }

    /** Leave still running on or after this day (pass a start-of-day date). */
    public function scopeEndingFrom(Builder $query, CarbonInterface $date): void
    {
        $query->where('to_date', '>=', $date);
    }

    /** Leave that has started by this day (pass a start-of-day date). */
    public function scopeStartingBy(Builder $query, CarbonInterface $date): void
    {
        $query->where('from_date', '<=', $date);
    }

    /** Leave of employees who currently work in the department. */
    public function scopeInDepartment(Builder $query, int $departmentId): void
    {
        $query->whereHas('user.employee', fn (Builder $employee) => $employee->where('department_id', $departmentId));
    }

    /** Leave never spans two years, so the start date decides the year. */
    public function scopeInYear(Builder $query, int $year): void
    {
        $start = Carbon::create($year)->startOfYear();

        $query->whereBetween('from_date', [$start, $start->copy()->endOfYear()]);
    }
}
