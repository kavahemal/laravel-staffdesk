<?php

namespace App\Http\Controllers;

use App\Enums\LeaveStatus;
use App\Http\Requests\LeaveDecisionRequest;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use App\Support\Sorts\RelatedColumnSort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class LeaveApprovalController extends Controller
{
    public function __construct(private readonly LeaveService $leaves) {}

    /**
     * "Leave requests": everyone's leave for HR/Admin, the own team's for a manager (never one's own).
     * Opens on Pending – the to-do list; ?filter[status]=all|approved|… shows decided leave too.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewApprovals', LeaveRequest::class);

        $user = $request->user();
        $byFrom = AllowedSort::field('from', 'from_date');

        $leaves = QueryBuilder::for(LeaveRequest::visibleToApprover($user)->with(['leaveType', 'actionedBy', 'user.employee.department']))
            ->allowedFilters([
                // An empty value would fall back to the default, so "All" is an explicit value.
                AllowedFilter::callback('status', fn (Builder $query, $value) => $value === 'all' ? $query : $query->where('status', $value))
                    ->default(LeaveStatus::Pending->value),
                AllowedFilter::callback('department', fn (Builder $query, $value) => $query->whereHas(
                    'user.employee', fn (Builder $employee) => $employee->where('department_id', $value)
                )),
                AllowedFilter::exact('type', 'leave_type_id'),
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
            ->orderBy('leave_requests.id')
            ->paginate(config('hrms.per_page'))
            ->withQueryString();

        return view('approvals.index', [
            'leaves' => $leaves,
            // A manager only sees their own team, so the department filter is for HR/Admin.
            'departments' => $user->can('leaves.approve-any') ? Department::orderBy('name')->get(['id', 'name']) : collect(),
            'leaveTypes' => LeaveType::orderBy('name')->get(['id', 'name']),
            'statuses' => LeaveStatus::cases(),
            'filters' => [
                'status' => (string) $request->input('filter.status', LeaveStatus::Pending->value),
                'department' => (int) $request->input('filter.department') ?: null,
                'type' => (int) $request->input('filter.type') ?: null,
            ],
        ]);
    }

    public function approve(LeaveDecisionRequest $request, LeaveRequest $leave): RedirectResponse
    {
        return $this->decide($request, $leave, LeaveStatus::Approved);
    }

    public function reject(LeaveDecisionRequest $request, LeaveRequest $leave): RedirectResponse
    {
        return $this->decide($request, $leave, LeaveStatus::Rejected);
    }

    private function decide(LeaveDecisionRequest $request, LeaveRequest $leave, LeaveStatus $decision): RedirectResponse
    {
        $done = $this->leaves->decide($leave, $request->user(), $decision, $request->validated('comment'));

        return $done
            ? back()->with('success', $decision === LeaveStatus::Approved ? 'Leave approved.' : 'Leave rejected.')
            : back()->with('error', 'This leave is no longer pending.');
    }
}
