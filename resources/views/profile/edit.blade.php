<x-app-layout>
    <x-slot name="header">Profile</x-slot>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>
