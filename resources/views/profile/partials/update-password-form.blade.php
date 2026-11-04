<section>
    <header>
        <h2 class="text-lg font-semibold text-gray-800">Update Password</h2>
        <p class="mt-1 text-sm text-gray-500">Ensure your account is using a long, random password to stay secure.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5" novalidate>
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Current Password" />
            <x-password-input id="update_password_current_password" name="current_password" bag="updatePassword" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="New Password" />
            <x-password-input id="update_password_password" name="password" bag="updatePassword" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Confirm Password" />
            <x-password-input id="update_password_password_confirmation" name="password_confirmation" bag="updatePassword" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <x-primary-button>Save</x-primary-button>
    </form>
</section>
