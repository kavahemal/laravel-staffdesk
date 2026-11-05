<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function __construct(private readonly LeaveService $leaves) {}

    /** Everything the dashboard shows; each part is null when the user's permissions don't cover it. */
    public function for(User $user): array
    {
        return [
            'approver' => $user->can('viewApprovals', LeaveRequest::class) ? $this->approverStats($user) : null,
            'own' => $user->can('leaves.apply') ? $this->ownLeave($user) : null,
        ];
    }

    /** Whole company for HR/Admin, the own team for a manager – same scope as the "Leave requests" page. */
    private function approverStats(User $user): array
    {
        $today = Carbon::today();
        $company = $user->can('leaves.approve-any');

        $onLeaveToday = LeaveRequest::visibleToApprover($user)
            ->where('status', LeaveStatus::Approved)
            ->overlapping($today, $today)
            ->with(['user', 'leaveType'])
            ->orderBy('from_date')
            ->orderBy('id')
            ->get();

        return [
            'company' => $company,
            'people' => Employee::active()->when(! $company, fn ($query) => $query->teamOf($user))->count(),
            'pending' => LeaveRequest::visibleToApprover($user)->where('status', LeaveStatus::Pending)->count(),
            'onLeaveToday' => $onLeaveToday,
        ];
    }

    private function ownLeave(User $user): array
    {
        return [
            'balances' => $this->leaves->balances($user, Carbon::today()->year)->values(),
            'recent' => $user->leaveRequests()->with('leaveType')
                ->latest()
                ->orderByDesc('id')
                ->limit(config('staffdesk.dashboard_recent_leaves'))
                ->get(),
        ];
    }
}
