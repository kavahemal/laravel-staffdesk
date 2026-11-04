<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white">
        {{-- Sign-in layout from TailAdmin: form on the left, brand panel on the right (lg+) --}}
        <div class="relative flex min-h-screen w-full flex-col lg:flex-row">
            <div class="flex w-full flex-1 flex-col px-6 py-10 lg:w-1/2">
                <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
                    <a href="{{ route('login') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                        <x-application-logo />
                        <span class="text-xl font-semibold text-gray-800">{{ config('app.name') }}</span>
                    </a>

                    {{ $slot }}
                </div>
            </div>

            <div class="relative hidden w-full items-center overflow-hidden bg-brand-950 lg:grid lg:w-1/2">
                <img src="{{ asset('images/grid-01.svg') }}" alt="" class="absolute top-0 right-0 w-full max-w-[250px] xl:max-w-[450px]">
                <img src="{{ asset('images/grid-01.svg') }}" alt="" class="absolute bottom-0 left-0 w-full max-w-[250px] rotate-180 xl:max-w-[450px]">

                <div class="relative z-1 flex flex-col items-center px-6">
                    <div class="mb-4 flex items-center gap-3">
                        <x-application-logo class="size-12" />
                        <span class="text-3xl font-semibold text-white">{{ config('app.name') }}</span>
                    </div>
                    <p class="max-w-xs text-center text-gray-400">
                        Employees, departments and leave management in one place.
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
