<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
        <h2 class="text-lg font-semibold text-gray-800">Welcome, {{ auth()->user()->name }}</h2>
        <p class="mt-1 text-sm text-gray-500">You're signed in.</p>
    </div>
</x-app-layout>
