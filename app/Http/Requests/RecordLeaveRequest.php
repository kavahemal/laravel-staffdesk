<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use Illuminate\Validation\Rule;

/** HR/Admin records leave for an employee: the apply fields plus who it is for. */
class RecordLeaveRequest extends StoreLeaveRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('record', LeaveRequest::class);
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'user_id' => [
                'required',
                'integer',
                // Nobody records their own leave (same as approving); Admin has no employee profile.
                Rule::notIn([$this->user()->id]),
                Rule::exists('users', 'id')->where('is_active', true),
                Rule::exists('employees', 'user_id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'user_id' => 'employee', 'reason' => 'reason'];
    }

    public function messages(): array
    {
        return [
            'user_id.not_in' => 'You can not record leave for yourself – apply for it instead.',
            'user_id.exists' => 'Choose an active employee.',
        ];
    }
}
