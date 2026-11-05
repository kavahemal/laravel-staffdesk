<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($header) ? strip_tags($header) . ' | ' : '' }}{{ $companyName }}</title>
    @if ($logoUrl)
        <link rel="icon" href="{{ $logoUrl }}">
    @else
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Flash messages are shown as toasts by resources/js/app.js --}}

<body @if (session('success')) data-flash-success="{{ session('success') }}" @endif @if (session('error')) data-flash-error="{{ session('error') }}" @endif>
    {{-- Layout from TailAdmin: fixed sidebar (90px collapsed / 290px expanded on xl+, slide-in below xl) --}}
    <div class="sidebar-expanded min-h-screen xl:flex" x-data :class="{ 'sidebar-expanded': $store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen }">

        {{-- Mobile backdrop --}}
        <div x-cloak x-show="$store.sidebar.isMobileOpen" @click="$store.sidebar.setMobileOpen(false)" x-transition.opacity.duration.200ms class="fixed inset-0 z-100 bg-black/50 backdrop-blur-xs xl:hidden"></div>

        @include('layouts.sidebar')

        <div class="min-w-0 flex-1 transition-all duration-300 ease-in-out xl:ml-[90px] xl:[.sidebar-expanded_&]:ml-[290px]">
            @include('layouts.header')

            <main class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                @isset($header)
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <h1 class="text-xl font-semibold text-gray-800">{{ $header }}</h1>
                        {{ $actions ?? '' }}
                    </div>
                @endisset

                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
