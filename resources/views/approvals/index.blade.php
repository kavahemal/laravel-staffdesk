<x-app-layout>
    <x-slot name="header">Leave requests</x-slot>

    @can('record', \App\Models\LeaveRequest::class)
        <x-slot name="actions">
            <x-primary-button :href="route('leaves.record')">
                <x-heroicon-o-plus />
                Record leave
            </x-primary-button>
        </x-slot>
    @endcan

    @php
        $pendingOnly = $filters['status'] === \App\Enums\LeaveStatus::Pending->value;
        $filtered = ! $pendingOnly || $filters['department'] || $filters['type'];
    @endphp

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <x-filter-bar :action="route('approvals.index')" :filtered="$filtered">
            <div class="sm:w-48">
                <x-select-input name="filter[status]" x-data @change="$el.form.submit()">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                    <option value="all" @selected($filters['status'] === 'all')>All statuses</option>
                </x-select-input>
            </div>

            @if ($departments->isNotEmpty())
                <div class="sm:w-56">
                    <x-select-input name="filter[department]" x-data @change="$el.form.submit()">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected($filters['department'] === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
            @endif

            <div class="sm:w-56">
                <x-select-input name="filter[type]" x-data @change="$el.form.submit()">
                    <option value="">All leave types</option>
                    @foreach ($leaveTypes as $leaveType)
                        <option value="{{ $leaveType->id }}" @selected($filters['type'] === $leaveType->id)>{{ $leaveType->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
        </x-filter-bar>

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
                        <th class="px-5 py-3 sm:px-6"></th>
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
                                @if ($leave->comment)
                                    <p class="mt-0.5 max-w-xs truncate text-theme-xs text-gray-500" title="{{ $leave->comment }}">{{ $leave->comment }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-gray-500 sm:px-6">{{ format_date($leave->created_at) }}</td>
                            <td class="px-5 py-4 sm:px-6">
                                @can('approve', $leave)
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('leaves.approve', $leave) }}" data-confirm="Approve leave?" data-confirm-button="Approve" data-confirm-style="primary" data-confirm-text="The employee will see your comment." data-confirm-comment>
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="comment">
                                            <button type="submit" title="Approve" class="rounded-lg p-2 text-gray-500 hover:bg-success-50 hover:text-success-600">
                                                <x-heroicon-o-check-circle class="size-5" />
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('leaves.reject', $leave) }}" data-confirm="Reject leave?" data-confirm-button="Reject" data-confirm-text="The employee will see your comment." data-confirm-comment>
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="comment">
                                            <button type="submit" title="Reject" class="rounded-lg p-2 text-gray-500 hover:bg-error-50 hover:text-error-500">
                                                <x-heroicon-o-x-circle class="size-5" />
                                            </button>
                                        </form>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500 sm:px-6">{{ $filtered ? 'No leave matches your filters.' : 'Nothing to approve right now.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $leaves->links() }}</div>
</x-app-layout>
