<x-app-layout>
    <x-slot name="header">Settings</x-slot>

    <div class="max-w-2xl rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-5" novalidate x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @method('PUT')

            <div>
                <h3 class="text-base font-medium text-gray-800">Work week</h3>
                <p class="mt-1 text-theme-sm text-gray-500">
                    How each weekday counts for leave: a full day counts 1, a half day 0.5, an off day 0.
                    Changes apply to new leave requests only.
                </p>
                <x-input-error :messages="$errors->get('work_week')" class="mt-2" />
            </div>

            <div class="divide-y divide-gray-100 rounded-xl border border-gray-200">
                @foreach ($dayNames as $day => $dayName)
                    <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <label for="work_week_{{ $day }}" class="text-sm font-medium text-gray-700">{{ $dayName }}</label>
                        <div class="sm:w-48">
                            <x-select-input id="work_week_{{ $day }}" name="work_week[{{ $day }}]">
                                @foreach ($options as $option)
                                    <option value="{{ $option->value }}" @selected(old("work_week.{$day}", $workWeek->day($day)->value) === $option->value)>{{ $option->label() }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('work_week.'.$day)" />
                        </div>
                    </div>
                @endforeach
            </div>

            <x-primary-button x-bind:disabled="busy">
                <span x-text="busy ? 'Saving…' : 'Save'">Save</span>
            </x-primary-button>
        </form>
    </div>
</x-app-layout>
