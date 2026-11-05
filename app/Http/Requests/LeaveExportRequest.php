<?php

namespace App\Http\Requests;

use App\Enums\LeaveStatus;
use App\Models\ReportExport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** "Export CSV" on the leave report: the report's current filters, all optional. */
class LeaveExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ReportExport::class);
    }

    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'array:from,to,type,status,department'],
            'filter.from' => ['nullable', 'date_format:Y-m-d'],
            'filter.to' => ['nullable', 'date_format:Y-m-d'],
            'filter.type' => ['nullable', 'integer', Rule::exists('leave_types', 'id')],
            'filter.status' => ['nullable', Rule::enum(LeaveStatus::class)],
            'filter.department' => ['nullable', 'integer', Rule::exists('departments', 'id')],
        ];
    }

    /** Filters with a value, e.g. ['from' => '2026-01-01', 'type' => 2]. */
    public function filters(): array
    {
        $filters = array_filter($this->validated('filter') ?? [], fn ($value) => $value !== null && $value !== '');

        foreach (['type', 'department'] as $id) {
            if (isset($filters[$id])) {
                $filters[$id] = (int) $filters[$id];
            }
        }

        return $filters;
    }
}
