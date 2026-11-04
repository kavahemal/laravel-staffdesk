<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee
            ? $this->user()->can('update', $employee)
            : $this->user()->can('create', Employee::class);
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($employee?->user_id)],
            'role' => ['required', Rule::in(config('hrms.employee_roles'))],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'designation' => ['required', 'string', 'max:100'],
            'joining_date' => ['required', 'date'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\-\s]+$/'],
            'password' => $employee ? ['prohibited'] : ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return ['department_id' => 'department'];
    }
}
