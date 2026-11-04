<x-guest-layout>
    <div class="mb-5 sm:mb-8">
        <h1 class="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md">{{ __('Reset Password') }}</h1>
        <p class="text-sm text-gray-500">{{ __('Choose a new password for your account.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email">{{ __('Email') }}<span class="text-error-500">*</span></x-input-label>
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password">{{ __('Password') }}<span class="text-error-500">*</span></x-input-label>
            <x-password-input id="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation">{{ __('Confirm Password') }}<span class="text-error-500">*</span></x-input-label>
            <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="w-full">{{ __('Reset Password') }}</x-primary-button>
    </form>
</x-guest-layout>
