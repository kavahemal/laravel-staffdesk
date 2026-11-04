<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeStatusController extends Controller
{
    public function __invoke(Request $request, Employee $employee, EmployeeService $employees): RedirectResponse
    {
        Gate::authorize('changeStatus', $employee);

        $active = $request->validate(['active' => ['required', 'boolean']])['active'];
        $employees->setActive($employee->load('user'), (bool) $active);

        return back()->with('success', $active ? 'Employee activated.' : 'Employee deactivated.');
    }
}
