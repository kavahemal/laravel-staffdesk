<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $leaveType = $this->route('leave_type');

        return $leaveType
            ? $this->user()->can('update', $leaveType)
            : $this->user()->can('create', LeaveType::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('leave_types')->ignore($this->route('leave_type'))],
            'yearly_quota' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['yearly_quota' => 'yearly quota'];
    }

    protected function prepareForValidation(): void
    {
        // An unticked checkbox sends nothing.
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
