<?php

namespace App\Policies;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;

class LeavePolicy
{
    /** "My leaves" – everyone who can take leave. */
    public function viewAny(User $user): bool
    {
        return $user->can('leaves.apply');
    }

    public function create(User $user): bool
    {
        return $user->can('leaves.apply');
    }

    /** Enter already-approved leave for an employee, e.g. sick leave reported by phone. */
    public function record(User $user): bool
    {
        return $user->can('leaves.record');
    }

    public function cancel(User $user, LeaveRequest $leave): bool
    {
        return $leave->user_id === $user->id && $leave->status === LeaveStatus::Pending;
    }

    /** The "Leave requests" page (team / all leave, approve and reject). */
    public function viewApprovals(User $user): bool
    {
        return $user->canAny(['leaves.approve-any', 'leaves.approve-team']);
    }

    /** Leave report with CSV export: everyone's leave, any status. */
    public function viewReport(User $user): bool
    {
        return $user->can('reports.view');
    }

    /** Approve or reject: HR/Admin for anyone, a manager for their own department – never one's own leave. */
    public function approve(User $user, LeaveRequest $leave): bool
    {
        if ($leave->status !== LeaveStatus::Pending || $leave->user_id === $user->id) {
            return false;
        }

        if ($user->can('leaves.approve-any')) {
            return true;
        }

        if (! $user->can('leaves.approve-team')) {
            return false;
        }

        // No extra queries when a list already eager-loaded these.
        $leave->loadMissing('user.employee.department');

        return $leave->user->employee?->department?->manager_id === $user->id;
    }
}
