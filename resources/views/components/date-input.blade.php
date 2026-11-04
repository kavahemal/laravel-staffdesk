{{--
    Date field with the flatpickr picker (set up in resources/js/app.js). Value is Y-m-d; shown as "15 Jan 2025".
    Extra options as attributes: data-datepicker="range", data-min-date="today", data-max-date="…", data-disable-sundays.
--}}
@props(['bag' => 'default'])

<div class="relative">
    <x-text-input :bag="$bag" {{ $attributes->merge(['data-datepicker' => 'single', 'data-display-format' => config('hrms.date_format'), 'autocomplete' => 'off', 'placeholder' => 'Select date', 'class' => 'pr-11']) }} />
    <x-heroicon-o-calendar-days class="pointer-events-none absolute top-1/2 right-4 size-5 -translate-y-1/2 text-gray-500" />
</div>
