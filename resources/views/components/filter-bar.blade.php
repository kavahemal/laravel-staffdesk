{{--
    GET filter form above a list: keeps the current ?sort, shows "Reset" when a filter is active.
    Usage: <x-filter-bar :action="route('employees.index')" :filtered="$filtered" button="Search"> …fields… </x-filter-bar>
--}}
@props(['action', 'filtered' => false, 'button' => 'Filter'])

<form method="GET" action="{{ $action }}" class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:px-6">
    @if (request()->filled('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}">
    @endif

    {{ $slot }}

    <div class="flex items-center gap-3">
        <x-secondary-button type="submit" class="py-2.5">{{ $button }}</x-secondary-button>
        @if ($filtered)
            <a href="{{ $action }}" class="text-sm text-gray-500 hover:text-brand-500">Reset</a>
        @endif
    </div>
</form>
