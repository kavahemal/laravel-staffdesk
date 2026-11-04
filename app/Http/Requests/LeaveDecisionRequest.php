<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Approve or reject a leave request, with an optional comment. */
class LeaveDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', $this->route('leave'));
    }

    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
