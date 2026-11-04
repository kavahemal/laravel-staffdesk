<?php

namespace App\Http\Requests;

use App\Enums\HalfDay;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Field format only; the leave rules (dates, overlap, balance) live in LeaveService. */
class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LeaveRequest::class);
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d'],
            'half_day' => ['nullable', Rule::enum(HalfDay::class)],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // A half day is always a single day, so the end date is not asked for.
        if ($this->filled('half_day')) {
            $this->merge(['to_date' => $this->input('from_date')]);
        }
    }

    public function attributes(): array
    {
        return [
            'leave_type_id' => 'leave type',
            'from_date' => 'start date',
            'to_date' => 'end date',
            'half_day' => 'half day',
        ];
    }
}
