<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class LeaveTypeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LeaveType::class);

        $byName = AllowedSort::field('name');

        $leaveTypes = QueryBuilder::for(LeaveType::class)
            ->allowedFilters([
                AllowedFilter::partial('search', 'name'),
            ])
            ->allowedSorts([
                $byName,
                AllowedSort::field('quota', 'yearly_quota'),
            ])
            ->defaultSort($byName)
            ->orderBy('leave_types.id')
            ->paginate(config('staffdesk.per_page'))
            ->withQueryString();

        return view('leave-types.index', [
            'leaveTypes' => $leaveTypes,
            'search' => (string) $request->input('filter.search', ''),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', LeaveType::class);

        return view('leave-types.form', ['leaveType' => new LeaveType(['is_active' => true])]);
    }

    public function store(LeaveTypeRequest $request): RedirectResponse
    {
        LeaveType::create($request->validated());

        return to_route('leave-types.index')->with('success', 'Leave type created.');
    }

    public function edit(LeaveType $leaveType): View
    {
        Gate::authorize('update', $leaveType);

        return view('leave-types.form', ['leaveType' => $leaveType]);
    }

    public function update(LeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $leaveType->update($request->validated());

        return to_route('leave-types.index')->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        Gate::authorize('delete', $leaveType);

        if ($leaveType->leaveRequests()->exists()) {
            return back()->with('error', "{$leaveType->name} is used by leave requests – deactivate it instead.");
        }

        $leaveType->delete();

        return to_route('leave-types.index')->with('success', 'Leave type deleted.');
    }
}
