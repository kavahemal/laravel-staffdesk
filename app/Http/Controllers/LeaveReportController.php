<?php

namespace App\Http\Controllers;

use App\Enums\LeaveStatus;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\Sorts\RelatedColumnSort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class LeaveReportController extends Controller
{
    /** Everyone's leave in any status, filtered by period, type, status and department. */
    public function index(Request $request): View
    {
        Gate::authorize('viewReport', LeaveRequest::class);

        $byFrom = AllowedSort::field('from', 'from_date');

        $query = QueryBuilder::for(LeaveRequest::with(['leaveType', 'actionedBy', 'user.employee.department']))
            ->allowedFilters([
                // The period keeps every leave that overlaps it; an invalid date is ignored like an empty one.
                AllowedFilter::callback('from', fn (Builder $query, $value) => ($date = $this->date($value)) ? $query->endingFrom($date) : $query),
                AllowedFilter::callback('to', fn (Builder $query, $value) => ($date = $this->date($value)) ? $query->startingBy($date) : $query),
                AllowedFilter::exact('type', 'leave_type_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('department', fn (Builder $query, $value) => $query->inDepartment((int) $value)),
            ])
            ->allowedSorts([
                $byFrom,
                AllowedSort::custom('employee', new RelatedColumnSort('users', 'name', 'user_id')),
                AllowedSort::custom('type', new RelatedColumnSort('leave_types', 'name', 'leave_type_id')),
                AllowedSort::field('days'),
                AllowedSort::field('status'),
                AllowedSort::field('applied', 'created_at'),
            ])
            ->defaultSort($byFrom)
            ->orderBy('leave_requests.id');

        $totalDays = (float) $query->clone()->reorder()->sum('days');

        return view('reports.leaves', [
            'leaves' => $query->paginate(config('hrms.per_page'))->withQueryString(),
            'totalDays' => $totalDays,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'leaveTypes' => LeaveType::orderBy('name')->get(['id', 'name']),
            'statuses' => LeaveStatus::cases(),
            'filters' => [
                'from' => $this->date($request->input('filter.from'))?->format('Y-m-d'),
                'to' => $this->date($request->input('filter.to'))?->format('Y-m-d'),
                'type' => (int) $request->input('filter.type') ?: null,
                'status' => (string) $request->input('filter.status'),
                'department' => (int) $request->input('filter.department') ?: null,
            ],
        ]);
    }

    /** A Y-m-d filter value as a start-of-day date; anything else (incl. 2026-02-31) is null. */
    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $date = Carbon::createFromFormat('!Y-m-d', $value);

        return $date->format('Y-m-d') === $value ? $date : null;
    }
}
