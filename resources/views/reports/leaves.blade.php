<x-app-layout>
    <x-slot name="header">Leave report</x-slot>

    @can('create', \App\Models\ReportExport::class)
        <x-slot name="actions">
            {{-- Exports what the filters show; the file arrives as a notification. --}}
            <form method="POST" action="{{ route('reports.leaves.export') }}" novalidate x-data="{ busy: false }" @submit="busy = true">
                @csrf
                @foreach (array_filter($filters) as $key => $value)
                    <input type="hidden" name="filter[{{ $key }}]" value="{{ $value }}">
                @endforeach
                <x-primary-button x-bind:disabled="busy">
                    <x-heroicon-o-arrow-down-tray />
                    <span x-text="busy ? 'Starting…' : 'Export CSV'">Export CSV</span>
                </x-primary-button>
            </form>
        </x-slot>
    @endcan

    @php
        $filtered = $filters['from'] || $filters['to'] || $filters['type'] || $filters['status'] !== '' || $filters['department'];
    @endphp

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <x-filter-bar :action="route('reports.leaves')" :filtered="$filtered">
            <div class="sm:w-44">
                <x-date-input name="filter[from]" :value="$filters['from']" placeholder="From date" />
            </div>

            <div class="sm:w-44">
                <x-date-input name="filter[to]" :value="$filters['to']" placeholder="To date" />
            </div>

            <div class="sm:w-44">
                <x-select-input name="filter[type]">
                    <option value="">All leave types</option>
                    @foreach ($leaveTypes as $leaveType)
                        <option value="{{ $leaveType->id }}" @selected($filters['type'] === $leaveType->id)>{{ $leaveType->name }}</option>
                    @endforeach
                </x-select-input>
            </div>

            <div class="sm:w-40">
                <x-select-input name="filter[status]">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-select-input>
            </div>

            <div class="sm:w-48">
                <x-select-input name="filter[department]">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected($filters['department'] === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
        </x-filter-bar>

        <p class="border-b border-gray-100 px-5 py-3 text-sm text-gray-500 sm:px-6">
            {{ $leaves->total() }} {{ Str::plural('request', $leaves->total()) }} · {{ format_days($totalDays) }} {{ Str::plural('day', $totalDays == 1 ? 1 : 2) }}
        </p>

        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead class="border-b border-gray-100">
                    <tr class="text-xs font-medium text-gray-500">
                        <x-sort-header field="employee" default="from">Employee</x-sort-header>
                        <x-sort-header field="type" default="from">Leave type</x-sort-header>
                        <x-sort-header field="from" default="from">Dates</x-sort-header>
                        <x-sort-header field="days" default="from">Days</x-sort-header>
                        <x-sort-header field="status" default="from">Status</x-sort-header>
                        <x-sort-header field="applied" default="from">Applied</x-sort-header>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($leaves as $leave)
                        <tr class="transition hover:bg-gray-50/50">
                            <td class="px-5 py-4 sm:px-6">
                                <p class="font-medium text-gray-800">{{ $leave->user->name }}</p>
                                <p class="text-theme-xs text-gray-500">
                                    {{ $leave->user->employee?->employee_code ?? '—' }} · {{ $leave->user->employee?->department?->name ?? 'No department' }}
                                </p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-800">{{ $leave->leaveType->name }}</p>
                                <p class="max-w-xs truncate text-theme-xs text-gray-500" title="{{ $leave->reason }}">{{ $leave->reason }}</p>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-gray-500 sm:px-6">{{ $leave->period() }}</td>
                            <td class="px-5 py-4 text-gray-500 sm:px-6">{{ format_days($leave->days) }}</td>
                            <td class="px-5 py-4 sm:px-6">
                                <x-badge :color="$leave->status->color()">{{ $leave->status->label() }}</x-badge>
                                @if ($leave->actionedBy)
                                    <p class="mt-1 text-theme-xs whitespace-nowrap text-gray-500">by {{ $leave->actionedBy->name }} · {{ format_date($leave->actioned_at) }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-gray-500 sm:px-6">{{ format_date($leave->created_at) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500 sm:px-6">{{ $filtered ? 'No leave matches your filters.' : 'No leave recorded yet.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $leaves->links() }}</div>
</x-app-layout>
