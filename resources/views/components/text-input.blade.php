{{-- Turns red when the field (matched by `name`) has a validation error; `bag` = named error bag. --}}
@props(['disabled' => false, 'bag' => 'default'])

@php
    $invalid = $errors->getBag($bag)->has($attributes->get('name'));
    $state = $invalid
        ? 'border-error-500 focus:border-error-300 focus:ring-error-500/10'
        : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10';
@endphp

<input @disabled($disabled) @if ($invalid) aria-invalid="true" @endif {{ $attributes->merge(['class' => "h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:ring-3 focus:outline-hidden disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 {$state}"]) }}>
