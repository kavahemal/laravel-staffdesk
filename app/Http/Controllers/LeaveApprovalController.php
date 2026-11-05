<?php

namespace App\Http\Controllers;

use App\Enums\LeaveStatus;
use App\Http\Requests\LeaveDecisionRequest;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use App\Support\Sorts\LeaveSorts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
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
        $byFrom = LeaveSorts::from();

        $leaves = QueryBuilder::for(LeaveRequest::visibleToApprover($user)->with(['leaveType', 'actionedBy', 'user.employee.department']))
            ->allowedFilters([
                // An empty value would fall back to the default, so "All" is an explicit value.
                AllowedFilter::callback('status', fn (Builder $query, $value) => $value === 'all' ? $query : $query->where('status', $value))
                    ->default(LeaveStatus::Pending->value),
                AllowedFilter::callback('department', fn (Builder $query, $value) => $query->inDepartment((int) $value)),
                AllowedFilter::exact('type', 'leave_type_id'),
            ])
            ->allowedSorts([$byFrom, ...LeaveSorts::columns(withEmployee: true)])
            ->defaultSort($byFrom)
            ->orderBy('leave_requests.id')
            ->paginate(config('staffdesk.per_page'))
            ->withQueryString();

        return view('approvals.index', [
            'leaves' => $leaves,
            'context' => $this->leaves->approvalContext($leaves->getCollection()),
            // A manager only sees their own team, so the department filter is for HR/Admin.
            'departments' => $user->can('leaves.approve-any') ? Department::forSelect()->get() : collect(),
            'leaveTypes' => LeaveType::forSelect()->get(),
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
