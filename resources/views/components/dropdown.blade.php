@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'p-3'])

@php
$alignmentClasses = match ($align) {
    'left' => 'origin-top-left left-0',
    'top' => 'origin-top',
    default => 'origin-top-right right-0',
};

$width = match ($width) {
    '48' => 'w-48',
    default => $width,
};
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false" @keydown.escape.window="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-cloak x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-3 {{ $width }} {{ $alignmentClasses }}"
            @click="open = false">
        <div class="flex flex-col gap-1 rounded-2xl border border-gray-200 bg-white shadow-theme-lg {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
