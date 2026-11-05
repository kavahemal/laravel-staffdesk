<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeService
{
    private const USER_FIELDS = ['name', 'email'];

    private const PROFILE_FIELDS = ['department_id', 'designation', 'joining_date', 'phone'];

    /** Creates the login account and the employee profile together – both or neither. */
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                ...collect($data)->only(self::USER_FIELDS)->all(),
                'password' => $data['password'],
            ]);
            $user->assignRole($data['role']);

            return $user->employee()->create([
                ...collect($data)->only(self::PROFILE_FIELDS)->all(),
                'employee_code' => $this->nextCode(),
            ]);
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $user = $employee->user;

            $user->update(collect($data)->only(self::USER_FIELDS)->all());
            $user->syncRoles([$data['role']]);

            if (! $user->can('leaves.approve-team')) {
                $this->releaseDepartments($user);
            }

            $employee->update(collect($data)->only(self::PROFILE_FIELDS)->all());

            return $employee;
        });
    }

    /** Turns the login on or off; a deactivated manager also stops managing departments. */
    public function setActive(Employee $employee, bool $active): void
    {
        DB::transaction(function () use ($employee, $active) {
            $employee->user->forceFill(['is_active' => $active])->save();

            if (! $active) {
                // Signs the user out of the API on every device.
                $employee->user->tokens()->delete();
                $this->releaseDepartments($employee->user);
            }
        });
    }

    /** A department must not keep a manager who can no longer approve its leave. */
    private function releaseDepartments(User $user): void
    {
        $user->managedDepartments()->update(['manager_id' => null]);
    }

    /** Next code after the highest one ever issued (deleted profiles included), e.g. EMP-0007. */
    private function nextCode(): string
    {
        $prefix = config('hrms.employee_code_prefix');

        $highest = Employee::withTrashed()->lockForUpdate()->pluck('employee_code')
            ->map(fn (string $code) => (int) Str::after($code, $prefix))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($highest + 1), config('hrms.employee_code_digits'), '0', STR_PAD_LEFT);
    }
}
