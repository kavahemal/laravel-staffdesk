{{-- Password field with a show / hide toggle (TailAdmin sign-in style). Attributes go to the <input>. --}}
@props(['bag' => 'default'])

<div x-data="{ show: false }" class="relative">
    <x-text-input type="password" x-bind:type="show ? 'text' : 'password'" :bag="$bag" {{ $attributes->merge(['class' => 'pr-11']) }} />

    <button type="button" @click="show = ! show" tabindex="-1"
            class="absolute top-1/2 right-4 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            x-bind:aria-label="show ? 'Hide password' : 'Show password'">
        <x-heroicon-o-eye class="size-5" x-show="! show" />
        <x-heroicon-o-eye-slash class="size-5" x-cloak x-show="show" />
    </button>
</div>
