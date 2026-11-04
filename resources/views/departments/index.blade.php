<x-app-layout>
    <x-slot name="header">Departments</x-slot>

    <x-slot name="actions">
        <x-primary-button :href="route('departments.create')">
            <x-heroicon-o-plus />
            Add department
        </x-primary-button>
    </x-slot>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <form method="GET" action="{{ route('departments.index') }}" class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:px-6">
            <div class="relative sm:w-80">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-gray-400" />
                <x-text-input name="filter[search]" type="search" :value="$search" placeholder="Search department…" class="pl-11" />
            </div>

            @if (request()->filled('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif

            <div class="flex items-center gap-3">
                <x-secondary-button type="submit" class="py-2.5">Search</x-secondary-button>
                @if ($search !== '')
                    <a href="{{ route('departments.index') }}" class="text-sm text-gray-500 hover:text-brand-500">Reset</a>
                @endif
            </div>
        </form>

        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead class="border-b border-gray-100">
                    <tr class="text-start text-xs font-medium text-gray-500">
                        <x-sort-header field="name" default="name">Name</x-sort-header>
                        <x-sort-header field="manager" default="name">Manager</x-sort-header>
                        <x-sort-header field="employees" default="name">Employees</x-sort-header>
                        <th class="px-5 py-3 sm:px-6"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($departments as $department)
                        <tr class="transition hover:bg-gray-50/50">
                            <td class="px-5 py-4 font-medium text-gray-800 sm:px-6">{{ $department->name }}</td>
                            <td class="px-5 py-4 text-gray-500 sm:px-6">{{ $department->manager?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-gray-500 sm:px-6">{{ $department->employees_count }}</td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('departments.edit', $department) }}" title="Edit" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand-500">
                                        <x-heroicon-o-pencil-square class="size-5" />
                                    </a>

                                    <form method="POST" action="{{ route('departments.destroy', $department) }}" data-confirm="Delete department?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete" class="rounded-lg p-2 text-gray-500 hover:bg-error-50 hover:text-error-500">
                                            <x-heroicon-o-trash class="size-5" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-gray-500 sm:px-6">{{ $search !== '' ? 'No departments match your search.' : 'No departments yet.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $departments->links() }}</div>
</x-app-layout>
