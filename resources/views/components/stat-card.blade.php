{{-- TailAdmin metric card. Usage: <x-stat-card title="Pending approvals" :value="3" icon="clock" :href="route('approvals.index')" /> --}}
@props(['title', 'value', 'icon', 'href' => null, 'hint' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 md:p-6']) }}>
    <div class="flex size-12 items-center justify-center rounded-xl bg-gray-100 text-gray-800">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-6" />
    </div>

    <div class="mt-5 flex items-end justify-between gap-3">
        <div class="min-w-0">
            <span class="block truncate text-sm text-gray-500">{{ $title }}</span>
            <h4 class="mt-2 text-lg font-bold text-gray-800">
                {{ $value }}
                @if ($hint)
                    <span class="text-theme-sm font-normal text-gray-500">{{ $hint }}</span>
                @endif
            </h4>
        </div>

        @if ($href)
            <a href="{{ $href }}" title="View" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand-500">
                <x-heroicon-o-arrow-right class="size-5" />
            </a>
        @endif
    </div>
</div>
