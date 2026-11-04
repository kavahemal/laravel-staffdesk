<x-guest-layout>
    @php
        $devLogin = app()->isLocal() ? config('hrms.dev_login') : [];
    @endphp

    <div class="mb-5 sm:mb-8">
        <h1 class="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md">Sign In</h1>
        <p class="text-sm text-gray-500">Enter your email and password to sign in.</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
        @csrf

        <div>
            <x-input-label for="email">Email<span class="text-error-500">*</span></x-input-label>
            <x-text-input id="email" type="email" name="email" :value="old('email', $devLogin['email'] ?? null)" required autofocus autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password">Password<span class="text-error-500">*</span></x-input-label>
            <x-password-input id="password" name="password" :value="$devLogin['password'] ?? null" required autocomplete="current-password" placeholder="Enter your password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex cursor-pointer items-center gap-3 text-sm text-gray-700 select-none">
                <input id="remember_me" type="checkbox" name="remember" class="size-4 rounded border-gray-300 accent-brand-500">
                Keep me logged in
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-brand-500 hover:text-brand-600">Forgot password?</a>
            @endif
        </div>

        <x-primary-button class="w-full">Sign In</x-primary-button>
    </form>
</x-guest-layout>
