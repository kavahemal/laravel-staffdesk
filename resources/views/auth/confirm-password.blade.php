<x-guest-layout>
    <div class="mb-5 sm:mb-8">
        <h1 class="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md">Confirm Password</h1>
        <p class="text-sm text-gray-500">This is a secure area of the application. Please confirm your password before continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5" novalidate>
        @csrf

        <div>
            <x-input-label for="password">Password<span class="text-error-500">*</span></x-input-label>
            <x-password-input id="password" name="password" required autofocus autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <x-primary-button class="w-full">Confirm</x-primary-button>
    </form>
</x-guest-layout>
