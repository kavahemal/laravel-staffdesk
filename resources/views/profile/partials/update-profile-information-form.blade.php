<section>
    <header>
        <h2 class="text-lg font-semibold text-gray-800">Profile Information</h2>
        <p class="mt-1 text-sm text-gray-500">Update your account's profile information and email address.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5" novalidate>
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button>Save</x-primary-button>
    </form>
</section>
