<x-app-layout>
    <x-slot name="header">Employees</x-slot>

    <x-slot name="actions">
        <x-primary-button :href="route('employees.create')">
            <x-heroicon-o-plus />
            Add employee
        </x-primary-button>
    </x-slot>

    @php
        $filtered = $filters['search'] || $filters['department'];
    @endphp

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <x-filter-bar :action="route('employees.index')" :filtered="$filtered" button="Search">
            <x-search-input :value="$filters['search']" placeholder="Search name, email, code…" />

            <div class="sm:w-56">
                <x-select-input name="filter[department]" x-data @change="$el.form.submit()">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected($filters['department'] === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
        </x-filter-bar>

        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead class="border-b border-gray-100">
                    <tr class="text-xs font-medium text-gray-500">
                        <x-sort-header field="code" default="code">Code</x-sort-header>
                        <x-sort-header field="name" default="code">Employee</x-sort-header>
                        <x-sort-header field="department" default="code">Department</x-sort-header>
                        <th class="px-5 py-3 text-start sm:px-6">Role</th>
                        <x-sort-header field="joined" default="code">Joined</x-sort-header>
                        <x-sort-header field="status" default="code">Status</x-sort-header>
                        <th class="px-5 py-3 sm:px-6"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($employees as $employee)
                        <tr class="transition hover:bg-gray-50/50">
                            <td class="px-5 py-4 whitespace-nowrap text-gray-500 sm:px-6">{{ $employee->employee_code }}</td>
                            <td class="px-5 py-4 sm:px-6">
                                <span class="block font-medium text-gray-800">{{ $employee->user->name }}</span>
                                <span class="block text-theme-xs text-gray-500">{{ $employee->designation }} · {{ $employee->user->email }}</span>
                            </td>
                            <td class="px-5 py-4 text-gray-500 sm:px-6">{{ $employee->department->name }}</td>
                            <td class="px-5 py-4 text-gray-500 sm:px-6">{{ $employee->user->roles->pluck('name')->implode(', ') }}</td>
                            <td class="px-5 py-4 whitespace-nowrap text-gray-500 sm:px-6">{{ format_date($employee->joining_date) }}</td>
                            <td class="px-5 py-4 sm:px-6">
                                <x-badge :color="$employee->user->is_active ? 'success' : 'gray'">
                                    {{ $employee->user->is_active ? 'Active' : 'Inactive' }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center justify-end gap-2">
                                    @can('update', $employee)
                                        <a href="{{ route('employees.edit', $employee) }}" title="Edit" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand-500">
                                            <x-heroicon-o-pencil-square class="size-5" />
                                        </a>
                                    @endcan

                                    @can('changeStatus', $employee)
                                        <form method="POST" action="{{ route('employees.status', $employee) }}" @if ($employee->user->is_active) data-confirm="Deactivate employee?" data-confirm-button="Deactivate"
                                                  data-confirm-text="They will be signed out and can't sign in until activated again."
                                              @else
                                                  data-confirm="Activate employee?" data-confirm-button="Activate" data-confirm-style="primary"
                                                  data-confirm-text="They will be able to sign in again." @endif>
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="active" value="{{ $employee->user->is_active ? 0 : 1 }}">
                                            @if ($employee->user->is_active)
                                                <button type="submit" title="Deactivate" class="rounded-lg p-2 text-gray-500 hover:bg-error-50 hover:text-error-500">
                                                    <x-heroicon-o-no-symbol class="size-5" />
                                                </button>
                                            @else
                                                <button type="submit" title="Activate" class="rounded-lg p-2 text-gray-500 hover:bg-success-50 hover:text-success-600">
                                                    <x-heroicon-o-check-circle class="size-5" />
                                                </button>
                                            @endif
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500 sm:px-6">{{ $filtered ? 'No employees match your search.' : 'No employees yet.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $employees->links() }}</div>
</x-app-layout>
