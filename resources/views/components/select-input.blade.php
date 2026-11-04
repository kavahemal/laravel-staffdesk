{{-- Native <select> styled like x-text-input (red on a validation error). Pass <option> elements in the slot. --}}
@props(['disabled' => false, 'bag' => 'default'])

@php
    // Array names like work_week[1] have their errors under work_week.1.
    $errorKey = str_replace(['[]', '[', ']'], ['', '.', ''], (string) $attributes->get('name'));
    $invalid = $errors->getBag($bag)->has($errorKey);
    $state = $invalid
        ? 'border-error-500 focus:border-error-300 focus:ring-error-500/10'
        : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10';
@endphp

<div class="relative">
    <select @disabled($disabled) @if ($invalid) aria-invalid="true" @endif {{ $attributes->merge(['class' => "h-11 w-full appearance-none rounded-lg border bg-transparent py-2.5 pr-11 pl-4 text-sm text-gray-800 shadow-theme-xs focus:ring-3 focus:outline-hidden disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 {$state}"]) }}>
        {{ $slot }}
    </select>
    <x-heroicon-o-chevron-down class="pointer-events-none absolute top-1/2 right-4 size-5 -translate-y-1/2 text-gray-500" />
</div>
