<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600 focus-visible:ring-3 focus-visible:ring-brand-500/20 focus-visible:outline-hidden disabled:pointer-events-none disabled:bg-brand-300']) }}>
    {{ $slot }}
</button>
