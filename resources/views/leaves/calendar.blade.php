<x-app-layout>
    <x-slot name="header">Leave calendar</x-slot>

    @php
        $link = fn (string $month) => route('leaves.calendar', array_filter(['month' => $month, 'department' => $department]));
        $isCurrentMonth = $month->isSameMonth(now());
        $hasLeave = collect($weeks)->flatten(1)->contains(fn (array $day) => $day['inMonth'] && $day['leaves']->isNotEmpty());
        $perDay = config('staffdesk.calendar_names_per_day');
    @endphp

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white" x-data="{ show: false, day: null, days: @js($details) }" @keydown.escape.window="show = false">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex items-center gap-2">
                <a href="{{ $link($month->copy()->subMonth()->format('Y-m')) }}" title="Previous month" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50 hover:text-gray-800">
                    <x-heroicon-o-chevron-left class="size-5" />
                </a>
                <h2 class="min-w-40 text-center text-lg font-semibold text-gray-800">{{ $month->format('F Y') }}</h2>
                <a href="{{ $link($month->copy()->addMonth()->format('Y-m')) }}" title="Next month" class="rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-gray-50 hover:text-gray-800">
                    <x-heroicon-o-chevron-right class="size-5" />
                </a>
                @unless ($isCurrentMonth)
                    <a href="{{ $link(now()->format('Y-m')) }}" class="ml-2 text-sm font-medium text-brand-500 hover:text-brand-600">Today</a>
                @endunless
            </div>

            @if ($departments->isNotEmpty())
                <form method="GET" action="{{ route('leaves.calendar') }}" class="sm:w-56">
                    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                    <x-select-input name="department" x-data @change="$el.form.submit()" aria-label="Department">
                        <option value="">All departments</option>
                        @foreach ($departments as $option)
                            <option value="{{ $option->id }}" @selected($department === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </x-select-input>
                </form>
            @endif
        </div>

        <div class="max-w-full overflow-x-auto">
            <div class="min-w-[720px]">
                <div class="grid grid-cols-7 border-b border-gray-100 text-xs font-medium text-gray-500">
                    @foreach ($weeks[0] as $day)
                        <div class="px-3 py-2">{{ $day['date']->format('D') }}</div>
                    @endforeach
                </div>

                @foreach ($weeks as $week)
                    <div class="grid grid-cols-7 border-b border-gray-100 last:border-b-0">
                        @foreach ($week as $day)
                            @php
                                $off = $day['workDay'] === \App\Enums\WorkDay::Off;
                                $count = $day['leaves']->count();
                            @endphp
                            <div @class([
                                'min-h-28 border-r border-gray-100 last:border-r-0',
                                'bg-gray-50' => $off || ! $day['inMonth'],
                            ])>
                                @if ($count)
                                    <button type="button" @click="day = @js($day['date']->toDateString()); show = true" title="See who is off" class="flex h-full w-full flex-col justify-start p-2 text-left transition hover:bg-gray-50">
                                @else
                                    <div class="p-2">
                                @endif
                                    <span class="mb-1 flex items-center justify-between gap-1">
                                        <span @class([
                                            'inline-flex size-6 items-center justify-center rounded-full text-theme-xs',
                                            'bg-brand-500 font-semibold text-white' => $day['today'],
                                            'text-gray-800' => ! $day['today'] && $day['inMonth'],
                                            'text-gray-400' => ! $day['today'] && ! $day['inMonth'],
                                        ])>{{ $day['date']->day }}</span>
                                        @if ($off)
                                            <span class="text-theme-xs text-gray-400">Off</span>
                                        @elseif ($day['workDay'] === \App\Enums\WorkDay::Half)
                                            <span class="text-theme-xs text-gray-400">Half day</span>
                                        @endif
                                    </span>

                                    <span class="block space-y-1">
                                        @foreach ($day['leaves']->take($perDay) as $leave)
                                            <span @class([
                                                'block truncate rounded px-1.5 py-0.5 text-theme-xs font-medium',
                                                'bg-brand-50 text-brand-700' => $leave->status !== \App\Enums\LeaveStatus::Pending,
                                                'border border-dashed border-warning-300 bg-warning-50 text-warning-700' => $leave->status === \App\Enums\LeaveStatus::Pending,
                                            ])>{{ $leave->user->name }}{{ $leave->half_day ? ' ½' : '' }}</span>
                                        @endforeach

                                        @if ($count > $perDay)
                                            <span class="block px-1.5 text-theme-xs font-medium text-brand-500">+{{ $count - $perDay }} more</span>
                                        @endif
                                    </span>
                                @if ($count)
                                    </button>
                                @else
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-gray-100 px-4 py-3 text-theme-xs text-gray-500 sm:px-6">
            <span class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-brand-50 ring-1 ring-brand-200"></span> Approved</span>
            <span class="flex items-center gap-1.5"><span class="size-3 rounded-sm border border-dashed border-warning-300 bg-warning-50"></span> Pending</span>
            <span class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-gray-100"></span> Off day / other month</span>
            <span>½ = half day · click a day for details</span>
            @unless ($hasLeave)
                <span class="sm:ml-auto">No leave in {{ $month->format('F') }}.</span>
            @endunless
        </div>

        {{-- Day details popup: everyone off that day. --}}
        <div x-show="show" x-transition.opacity x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="calendar-day-title">
            <div class="absolute inset-0 bg-gray-900/50" @click="show = false"></div>

            <div class="relative flex max-h-[85vh] w-full max-w-lg flex-col rounded-2xl bg-white shadow-xl" x-show="show" x-transition>
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 lg:px-6">
                    <div>
                        <h3 id="calendar-day-title" class="text-base font-semibold text-gray-800" x-text="days[day]?.title"></h3>
                        <p class="text-theme-xs text-gray-500" x-text="(days[day]?.leaves.length ?? 0) + ' off'"></p>
                    </div>
                    <button type="button" @click="show = false" title="Close" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-800">
                        <x-heroicon-o-x-mark class="size-5" />
                    </button>
                </div>

                <ul class="divide-y divide-gray-100 overflow-y-auto px-5 text-sm lg:px-6">
                    <template x-for="(leave, index) in days[day]?.leaves ?? []" :key="index">
                        <li class="flex items-start justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800" x-text="leave.name"></p>
                                <p class="text-theme-xs text-gray-500" x-text="leave.details"></p>
                                <p class="mt-0.5 text-theme-xs text-gray-500" x-text="leave.type + ' · ' + leave.period"></p>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-theme-xs font-medium"
                                  :class="leave.pending ? 'bg-warning-50 text-warning-600' : 'bg-success-50 text-success-600'"
                                  x-text="leave.status"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
