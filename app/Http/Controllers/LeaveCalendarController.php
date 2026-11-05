<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Services\LeaveCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LeaveCalendarController extends Controller
{
    /** ?month=2026-11 (default: this month); HR/Admin may add ?department=3. */
    public function __invoke(Request $request, LeaveCalendarService $calendar): View
    {
        Gate::authorize('viewCalendar', LeaveRequest::class);

        $user = $request->user();
        $everyone = $user->can('leaves.approve-any');
        $month = $calendar->parseMonth($request->query('month'));
        $department = $everyone ? ((int) $request->query('department') ?: null) : null;

        $weeks = $calendar->weeks($user, $month, $department);

        return view('leaves.calendar', [
            'month' => $month,
            'weeks' => $weeks,
            'details' => $calendar->dayDetails($weeks),
            'departments' => $everyone ? Department::forSelect()->get() : collect(),
            'department' => $department,
        ]);
    }
}
