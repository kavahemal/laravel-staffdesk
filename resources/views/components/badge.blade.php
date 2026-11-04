{{-- TailAdmin "light" badge. Usage: <x-badge color="success">Active</x-badge> --}}
@props(['color' => 'gray'])

@php
    $colors = [
        'success' => 'bg-success-50 text-success-600',
        'error' => 'bg-error-50 text-error-600',
        'warning' => 'bg-warning-50 text-warning-600',
        'brand' => 'bg-brand-50 text-brand-500',
        'gray' => 'bg-gray-100 text-gray-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-theme-xs font-medium whitespace-nowrap '.($colors[$color] ?? $colors['gray'])]) }}>{{ $slot }}</span>
