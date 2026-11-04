<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employees.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('employees.manage');
    }

    /** Own details go through the Profile page, so nobody can change their own role here. */
    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.manage') && $employee->user_id !== $user->id;
    }

    /** Activate / deactivate the employee's login. */
    public function changeStatus(User $user, Employee $employee): bool
    {
        return $user->can('users.activate') && $employee->user_id !== $user->id;
    }
}
