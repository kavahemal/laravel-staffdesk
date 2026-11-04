<?php

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\User;

class LeaveTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leave-types.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('leave-types.manage');
    }

    public function update(User $user, LeaveType $leaveType): bool
    {
        return $user->can('leave-types.manage');
    }

    public function delete(User $user, LeaveType $leaveType): bool
    {
        return $user->can('leave-types.manage');
    }
}
