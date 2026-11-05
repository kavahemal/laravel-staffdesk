<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use App\Models\User;
use App\Support\Sorts\RelatedColumnSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Department::class);

        $byName = AllowedSort::field('name');

        $departments = QueryBuilder::for(Department::class)
            ->with('manager')
            ->withCount('employees')
            ->allowedFilters([
                AllowedFilter::partial('search', 'name'),
            ])
            ->allowedSorts([
                $byName,
                AllowedSort::custom('manager', new RelatedColumnSort('users', 'name', 'manager_id')),
                AllowedSort::field('employees', 'employees_count'),
            ])
            ->defaultSort($byName)
            ->orderBy('departments.id')
            ->paginate(config('hrms.per_page'))
            ->withQueryString();

        return view('departments.index', [
            'departments' => $departments,
            'search' => (string) $request->input('filter.search', ''),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Department::class);

        return view('departments.form', ['department' => new Department, 'managers' => $this->managers()]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return to_route('departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department): View
    {
        Gate::authorize('update', $department);

        return view('departments.form', ['department' => $department, 'managers' => $this->managers()]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return to_route('departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        Gate::authorize('delete', $department);

        if ($department->employees()->exists()) {
            return back()->with('error', "Move or remove the employees of {$department->name} before deleting it.");
        }

        $department->delete();

        return to_route('departments.index')->with('success', 'Department deleted.');
    }

    private function managers(): Collection
    {
        return User::teamManagers()->orderBy('name')->get(['id', 'name']);
    }
}
