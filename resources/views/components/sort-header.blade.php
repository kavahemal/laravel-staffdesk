{{--
    Clickable column header for lists built with Spatie Query Builder.
    Usage: <x-sort-header field="name" default="code">Employee</x-sort-header>
    `default` = the list's defaultSort, so its column shows the arrow before anything is clicked.
--}}
@props(['field', 'default' => null])

@php
    $current = request()->query('sort', $default);
    $ascending = $current === $field;
    $descending = $current === "-{$field}";
    $url = request()->fullUrlWithQuery(['sort' => $ascending ? "-{$field}" : $field, 'page' => null]);
@endphp

<th {{ $attributes->merge(['class' => 'px-5 py-3 text-start sm:px-6']) }} @if ($ascending || $descending) aria-sort="{{ $ascending ? 'ascending' : 'descending' }}" @endif>
    <a href="{{ $url }}" @class(['inline-flex items-center gap-1 hover:text-gray-800', 'text-gray-800' => $ascending || $descending])>
        {{ $slot }}
        @if ($ascending)
            <x-heroicon-o-chevron-up class="size-3.5" />
        @elseif ($descending)
            <x-heroicon-o-chevron-down class="size-3.5" />
        @else
            <x-heroicon-o-chevron-up-down class="size-3.5 text-gray-400" />
        @endif
    </a>
</th>
