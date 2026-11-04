<x-guest-layout>
    <div class="mb-5 sm:mb-8">
        <h1 class="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md">{{ __('Forgot Password?') }}</h1>
        <p class="text-sm text-gray-500">{{ __('Enter your email address and we will send you a link to choose a new password.') }}</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email">{{ __('Email') }}<span class="text-error-500">*</span></x-input-label>
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button class="w-full">{{ __('Email Password Reset Link') }}</x-primary-button>
    </form>

    <p class="mt-5 text-center text-sm text-gray-700 sm:text-start">
        {{ __('Remember your password?') }}
        <a href="{{ route('login') }}" class="text-brand-500 hover:text-brand-600">{{ __('Sign In') }}</a>
    </p>
</x-guest-layout>
