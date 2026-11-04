{{-- Password field with a show / hide toggle (TailAdmin sign-in style). Attributes go to the <input>. --}}
<div x-data="{ show: false }" class="relative">
    <x-text-input type="password" x-bind:type="show ? 'text' : 'password'" {{ $attributes->merge(['class' => 'pr-11']) }} />

    <button type="button" @click="show = ! show" tabindex="-1"
            class="absolute top-1/2 right-4 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            x-bind:aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'">
        <x-icon name="eye" x-show="! show" />
        <x-icon name="eye-off" x-cloak x-show="show" />
    </button>
</div>
