<x-app-layout>
    <x-slot name="header">{{ __('Dashboard') }}</x-slot>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ __("You're signed in.") }}</p>
    </div>
</x-app-layout>
