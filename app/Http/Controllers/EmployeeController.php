<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Support\Filters\MultiColumnSearch;
use App\Support\Sorts\RelatedColumnSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $employees) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Employee::class);

        // ?filter[search]=…&filter[department]=3&sort=-joined – anything not listed here is rejected with 400.
        // The default must be the AllowedSort object: a plain string is used as a raw column name.
        $byCode = AllowedSort::field('code', 'employee_code');

        $employees = QueryBuilder::for(Employee::class)
            ->with(['user.roles', 'department'])
            ->allowedFilters([
                AllowedFilter::custom('search', new MultiColumnSearch(['employee_code', 'designation', 'user.name', 'user.email'])),
                AllowedFilter::exact('department', 'department_id'),
            ])
            ->allowedSorts([
                $byCode,
                AllowedSort::custom('name', new RelatedColumnSort('users', 'name', 'user_id')),
                AllowedSort::custom('department', new RelatedColumnSort('departments', 'name', 'department_id')),
                AllowedSort::field('joined', 'joining_date'),
                AllowedSort::custom('status', new RelatedColumnSort('users', 'is_active', 'user_id')),
            ])
            ->defaultSort($byCode)
            ->orderBy('employees.id')
            ->paginate(config('staffdesk.per_page'))
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'departments' => Department::forSelect()->get(),
            'filters' => [
                'search' => (string) $request->input('filter.search', ''),
                'department' => (int) $request->input('filter.department') ?: null,
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Employee::class);

        return view('employees.form', $this->formData(new Employee));
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $employee = $this->employees->create($request->validated());

        return to_route('employees.index')
            ->with('success', "Employee {$employee->employee_code} created.");
    }

    public function edit(Employee $employee): View
    {
        Gate::authorize('update', $employee);

        return view('employees.form', $this->formData($employee->load('user.roles')));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->employees->update($employee->load('user'), $request->validated());

        return to_route('employees.index')->with('success', 'Employee updated.');
    }

    private function formData(Employee $employee): array
    {
        return [
            'employee' => $employee,
            'departments' => Department::forSelect()->get(),
            'roles' => config('staffdesk.employee_roles'),
        ];
    }
}
