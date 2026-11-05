<?php

namespace App\Http\Controllers;

use App\Enums\HalfDay;
use App\Enums\LeaveStatus;
use App\Http\Requests\RecordLeaveRequest;
use App\Http\Requests\StoreLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use App\Support\LeaveBalance;
use App\Support\Sorts\LeaveSorts;
use App\Support\WorkWeek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Enums\SortDirection;
use Spatie\QueryBuilder\QueryBuilder;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveService $leaves) {}

    /** "My leaves" – the signed-in user's own requests. */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LeaveRequest::class);

        $byFrom = LeaveSorts::from()->defaultDirection(SortDirection::DESCENDING);

        $leaves = QueryBuilder::for($request->user()->leaveRequests()->with('leaveType'))
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type', 'leave_type_id'),
            ])
            ->allowedSorts([$byFrom, ...LeaveSorts::columns()])
            ->defaultSort($byFrom)
            ->orderByDesc('leave_requests.id')
            ->paginate(config('hrms.per_page'))
            ->withQueryString();

        return view('leaves.index', [
            'leaves' => $leaves,
            'leaveTypes' => LeaveType::forSelect()->get(),
            'statuses' => LeaveStatus::cases(),
            'filters' => [
                'status' => (string) $request->input('filter.status', ''),
                'type' => (int) $request->input('filter.type') ?: null,
            ],
        ]);
    }

    public function create(Request $request, WorkWeek $workWeek): View
    {
        Gate::authorize('create', LeaveRequest::class);

        $year = Carbon::today()->year;

        $leaveTypes = $this->leaves->balances($request->user(), $year)
            ->filter(fn (LeaveBalance $balance) => $balance->type->is_active)
            ->map(fn (LeaveBalance $balance) => $balance->type->name.' ('.($balance->remaining() === null
                ? 'unlimited'
                : format_days($balance->remaining()).' days left in '.$year).')');

        return view('leaves.create', [
            ...$this->formData($workWeek, $leaveTypes),
            'action' => route('leaves.store'),
            'cancelUrl' => route('leaves.index'),
        ]);
    }

    /** HR/Admin: enter already-approved leave for an employee (past dates of this year allowed). */
    public function record(Request $request, WorkWeek $workWeek): View
    {
        Gate::authorize('record', LeaveRequest::class);

        return view('leaves.create', [
            ...$this->formData($workWeek, LeaveType::where('is_active', true)->orderBy('name')->pluck('name', 'id')),
            'action' => route('leaves.record.store'),
            'cancelUrl' => route('approvals.index'),
            'employees' => User::active()
                ->whereKeyNot($request->user()->id)
                ->whereHas('employee')
                ->with('employee:id,user_id,employee_code')
                ->orderBy('name')
                ->get(['id', 'name']),
            'minDate' => Carbon::today()->startOfYear()->toDateString(),
            'maxDate' => Carbon::today()->endOfYear()->toDateString(),
        ]);
    }

    public function storeRecord(RecordLeaveRequest $request): RedirectResponse
    {
        $employee = User::findOrFail($request->validated('user_id'));

        $this->leaves->apply($employee, $request->safe()->except('user_id'), recordedBy: $request->user());

        return to_route('approvals.index')->with('success', "Leave recorded for {$employee->name}.");
    }

    /** Quota / approved / pending / remaining per leave type for the current year. */
    public function balance(Request $request): View
    {
        Gate::authorize('viewAny', LeaveRequest::class);

        $year = Carbon::today()->year;

        return view('leaves.balance', [
            'balances' => $this->leaves->balances($request->user(), $year),
            'year' => $year,
        ]);
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $this->leaves->apply($request->user(), $request->validated());

        return to_route('leaves.index')->with('success', 'Leave request submitted.');
    }

    public function cancel(LeaveRequest $leave): RedirectResponse
    {
        Gate::authorize('cancel', $leave);

        return $this->leaves->cancel($leave)
            ? back()->with('success', 'Leave cancelled.')
            : back()->with('error', 'This leave can no longer be cancelled.');
    }

    /** @param  Collection<int, string>  $leaveTypes  option label per leave type id */
    private function formData(WorkWeek $workWeek, Collection $leaveTypes): array
    {
        return [
            'leaveTypes' => $leaveTypes,
            'halfDays' => HalfDay::cases(),
            'workWeek' => $workWeek,
            'dayNames' => WorkWeek::dayNames(),
        ];
    }
}
