<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');

        return $department
            ? $this->user()->can('update', $department)
            : $this->user()->can('create', Department::class);
    }

    public function rules(): array
    {
        return [
            // Soft-deleted departments keep their name reserved (the column is unique).
            'name' => ['required', 'string', 'max:100', Rule::unique('departments')->ignore($this->route('department'))],
            'manager_id' => ['nullable', 'integer', Rule::in(User::teamManagers()->pluck('id'))],
        ];
    }

    public function attributes(): array
    {
        return ['manager_id' => 'manager'];
    }

    public function messages(): array
    {
        return ['manager_id.in' => 'The manager must be an active user with the Manager role.'];
    }
}
