{{--
    The company logo uploaded on the Settings page, or the default StaffDesk mark (brand square with a "people" glyph).
    `product` = always the StaffDesk mark (signed-out pages).
--}}
@props(['product' => false])

@php
    $logoUrl = $product ? null : app(\App\Services\SettingService::class)->logoUrl();
@endphp

@if ($logoUrl)
    <img src="{{ $logoUrl }}" alt="" {{ $attributes->merge(['class' => 'size-8 shrink-0 rounded-lg object-contain']) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-white']) }}>
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
    </span>
@endif
