{{-- Apply leave (own) and Record leave (HR/Admin for an employee – when $employees is set). --}}
<x-app-layout>
    @php
        $recording = isset($employees);
        $offDays = collect($workWeek->offDays())->map(fn($day) => $dayNames[$day])->join(', ');
        $halfWorkDays = collect($workWeek->halfDays())->map(fn($day) => $dayNames[$day])->join(', ');
        $reasonInvalid = $errors->has('reason');
        $minDate = $recording ? $minDate : 'today';
        $maxDate = $recording ? $maxDate : null;
    @endphp

    <x-slot name="header">{{ $recording ? 'Record leave' : 'Apply leave' }}</x-slot>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
        @if ($recording)
            <p class="mb-5 text-theme-sm text-gray-500">
                For leave that was agreed outside the app, e.g. sick leave reported by phone. It is saved as approved
                and the employee is notified. Earlier dates of this year are allowed.
            </p>
        @endif

        <form method="POST" action="{{ $action }}" class="space-y-5" novalidate x-data="{ busy: false, half: @js((string) old('half_day', '')) }" @submit="busy = true">
            @csrf

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                @if ($recording)
                    <div>
                        <x-input-label for="user_id">Employee<span class="text-error-500">*</span></x-input-label>
                        <x-select-input id="user_id" name="user_id" required>
                            <option value="">Select employee</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) old('user_id') === (string) $employee->id)>{{ $employee->name }} ({{ $employee->employee->employee_code }})</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('user_id')" />
                    </div>
                @endif

                <div>
                    <x-input-label for="leave_type_id">Leave type<span class="text-error-500">*</span></x-input-label>
                    <x-select-input id="leave_type_id" name="leave_type_id" required>
                        <option value="">Select leave type</option>
                        @foreach ($leaveTypes as $id => $label)
                            <option value="{{ $id }}" @selected((string) old('leave_type_id') === (string) $id)>{{ $label }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('leave_type_id')" />
                </div>

                <div>
                    <x-input-label for="half_day" value="Duration" />
                    <x-select-input id="half_day" name="half_day" x-model="half">
                        <option value="">Full day(s)</option>
                        @foreach ($halfDays as $halfDay)
                            <option value="{{ $halfDay->value }}" @selected(old('half_day') === $halfDay->value)>Half day – {{ strtolower($halfDay->label()) }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('half_day')" />
                </div>

                @if ($recording)
                    <div class="hidden sm:block"></div>
                @endif

                <div>
                    <x-input-label for="from_date">
                        <span x-text="half ? 'Date' : 'From'">From</span><span class="text-error-500">*</span>
                    </x-input-label>
                    <x-date-input id="from_date" name="from_date" :value="old('from_date')" :data-min-date="$minDate" :data-max-date="$maxDate" :data-disable-weekdays="implode(',', $workWeek->offDays())" required />
                    <x-input-error :messages="$errors->get('from_date')" />
                </div>

                <div x-show="! half">
                    <x-input-label for="to_date">To<span class="text-error-500">*</span></x-input-label>
                    <x-date-input id="to_date" name="to_date" :value="old('to_date')" :data-min-date="$minDate" data-min-date-from="from_date" :data-max-date="$maxDate" :data-disable-weekdays="implode(',', $workWeek->offDays())" required />
                    <x-input-error :messages="$errors->get('to_date')" />
                </div>
            </div>

            <p class="text-theme-xs text-gray-500">
                @if ($offDays !== '')
                    {{ $offDays }} {{ count($workWeek->offDays()) === 1 ? 'is' : 'are' }} off and not counted.
                @endif
                @if ($halfWorkDays !== '')
                    {{ $halfWorkDays }} {{ count($workWeek->halfDays()) === 1 ? 'counts' : 'count' }} as half a day.
                @endif
                Half-day leave is possible on full working days.
            </p>

            <div>
                <x-input-label for="reason">Reason<span class="text-error-500">*</span></x-input-label>
                <textarea id="reason" name="reason" rows="3" maxlength="500" required @if ($reasonInvalid) aria-invalid="true" @endif @if ($recording) placeholder="e.g. Sick, informed by phone" @endif @class([
                    'w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:ring-3 focus:outline-hidden',
                    'border-error-500 focus:border-error-300 focus:ring-error-500/10' => $reasonInvalid,
                    'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10' => !$reasonInvalid,
                ])>{{ old('reason') }}</textarea>
                <x-input-error :messages="$errors->get('reason')" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button x-bind:disabled="busy">
                    <span x-text="busy ? 'Saving…' : '{{ $recording ? 'Record leave' : 'Submit' }}'">{{ $recording ? 'Record leave' : 'Submit' }}</span>
                </x-primary-button>
                <x-secondary-button :href="$cancelUrl">Cancel</x-secondary-button>
            </div>
        </form>
    </div>
</x-app-layout>
