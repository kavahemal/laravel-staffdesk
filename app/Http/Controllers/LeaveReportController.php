<?php

namespace App\Http\Controllers;

use App\Enums\LeaveStatus;
use App\Http\Queries\LeaveReportQuery;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LeaveReportController extends Controller
{
    /** Everyone's leave in any status, filtered by period, type, status and department. */
    public function index(Request $request): View
    {
        Gate::authorize('viewReport', LeaveRequest::class);

        $query = LeaveReportQuery::for($request);

        $totalDays = (float) $query->clone()->reorder()->sum('days');

        return view('reports.leaves', [
            'leaves' => $query->paginate(config('hrms.per_page'))->withQueryString(),
            'totalDays' => $totalDays,
            'departments' => Department::forSelect()->get(),
            'leaveTypes' => LeaveType::forSelect()->get(),
            'statuses' => LeaveStatus::cases(),
            'filters' => [
                'from' => LeaveReportQuery::date($request->input('filter.from'))?->format('Y-m-d'),
                'to' => LeaveReportQuery::date($request->input('filter.to'))?->format('Y-m-d'),
                'type' => (int) $request->input('filter.type') ?: null,
                'status' => (string) $request->input('filter.status'),
                'department' => (int) $request->input('filter.department') ?: null,
            ],
        ]);
    }
}
