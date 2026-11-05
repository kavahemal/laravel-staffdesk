<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    @can('leaves.apply')
        <x-slot name="actions">
            <x-primary-button :href="route('leaves.create')">
                <x-heroicon-o-plus />
                Apply leave
            </x-primary-button>
        </x-slot>
    @endcan

    <div class="space-y-6">
        @if ($approver)
            <section>
                <h2 class="mb-4 text-lg font-semibold text-gray-800">{{ $approver['company'] ? 'Company' : 'My team' }}</h2>

                <div class="grid gap-4 sm:grid-cols-3 md:gap-6">
                    <x-stat-card :title="$approver['company'] ? 'Active employees' : 'Team members'" :value="$approver['people']" icon="users"
                        :href="auth()->user()->can('employees.manage') ? route('employees.index') : null" />
                    <x-stat-card title="On leave today" :value="$approver['onLeaveToday']->count()" icon="sun" />
                    <x-stat-card title="Pending approvals" :value="$approver['pending']" icon="clipboard-document-check" :href="route('approvals.index')" />
                </div>

                <div class="mt-4 rounded-2xl border border-gray-200 bg-white md:mt-6">
                    <h3 class="border-b border-gray-100 px-5 py-4 font-medium text-gray-800 sm:px-6">On leave today</h3>

                    <ul class="divide-y divide-gray-100 text-sm">
                        @forelse ($approver['onLeaveToday'] as $leave)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 sm:px-6">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $leave->user->name }}</p>
                                    <p class="text-theme-xs text-gray-500">{{ $leave->leaveType->name }}</p>
                                </div>
                                <span class="text-gray-500">{{ $leave->period() }}</span>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-gray-500 sm:px-6">Nobody is on leave today.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        @endif

        @if ($own)
            <section>
                <h2 class="mb-4 text-lg font-semibold text-gray-800">My leave {{ now()->year }}</h2>

                <div class="grid gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
                    @forelse ($own['balances'] as $balance)
                        <x-stat-card :title="$balance->type->name" icon="calendar-days" :href="route('leaves.balance')"
                            :value="$balance->remaining() === null ? 'Unlimited' : format_days($balance->remaining())"
                            :hint="$balance->remaining() === null ? null : 'of '.$balance->quota().' days left'" />
                    @empty
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center text-gray-500 sm:col-span-2 xl:col-span-4">No leave types yet.</div>
                    @endforelse
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white md:mt-6">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 sm:px-6">
                        <h3 class="font-medium text-gray-800">Recent requests</h3>
                        <a href="{{ route('leaves.index') }}" class="text-theme-sm font-medium text-brand-500 hover:text-brand-600">View all</a>
                    </div>

                    <div class="max-w-full overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b border-gray-100">
                                <tr class="text-left text-xs font-medium text-gray-500">
                                    <th class="px-5 py-3 sm:px-6">Leave type</th>
                                    <th class="px-5 py-3 sm:px-6">Dates</th>
                                    <th class="px-5 py-3 sm:px-6">Days</th>
                                    <th class="px-5 py-3 sm:px-6">Status</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 text-sm">
                                @forelse ($own['recent'] as $leave)
                                    <tr>
                                        <td class="px-5 py-4 font-medium text-gray-800 sm:px-6">{{ $leave->leaveType->name }}</td>
                                        <td class="px-5 py-4 whitespace-nowrap text-gray-500 sm:px-6">{{ $leave->period() }}</td>
                                        <td class="px-5 py-4 text-gray-500 sm:px-6">{{ format_days($leave->days) }}</td>
                                        <td class="px-5 py-4 sm:px-6"><x-badge :color="$leave->status->color()">{{ $leave->status->label() }}</x-badge></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-10 text-center text-gray-500 sm:px-6">You have not applied for leave yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
