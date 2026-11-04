{{-- Search box for a list's <x-filter-bar>; sends ?filter[search]=… --}}
@props(['value' => '', 'placeholder' => 'Search…'])

<div class="relative sm:w-80">
    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-gray-400" />
    <x-text-input name="filter[search]" type="search" :value="$value" :placeholder="$placeholder" class="pl-11" />
</div>
