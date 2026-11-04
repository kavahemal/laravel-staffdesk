@php
    $menu = collect(config('menu'))
        ->filter(fn (array $item) => empty($item['permission']) || auth()->user()->can($item['permission']));
@endphp

{{-- Labels show when the sidebar is expanded (xl+) and always below xl, where the sidebar is a full-width overlay. --}}
<aside id="sidebar"
       class="fixed top-0 left-0 z-50 flex h-screen w-[290px] flex-col border-r border-gray-200 bg-white px-5 transition-all duration-300 ease-in-out max-xl:z-101 max-xl:duration-200 xl:w-[90px] xl:[.sidebar-expanded_&]:w-[290px]"
       :class="$store.sidebar.isMobileOpen ? 'translate-x-0' : 'max-xl:-translate-x-full'"
       @mouseenter="$store.sidebar.setHovered(true)"
       @mouseleave="$store.sidebar.setHovered(false)">

    <div class="flex items-center py-8 xl:justify-center xl:[.sidebar-expanded_&]:justify-start">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <x-application-logo />
            <span class="text-xl font-semibold text-gray-800 xl:hidden xl:[.sidebar-expanded_&]:inline">{{ config('app.name') }}</span>
        </a>
    </div>

    <div class="no-scrollbar flex flex-col overflow-y-auto">
        <nav class="mb-6" aria-label="{{ __('Main menu') }}">
            <h2 class="mb-4 flex text-xs leading-5 text-gray-400 uppercase xl:justify-center xl:[.sidebar-expanded_&]:justify-start">
                <span class="xl:hidden xl:[.sidebar-expanded_&]:inline">{{ __('Menu') }}</span>
                <x-icon name="dots" class="hidden xl:block xl:[.sidebar-expanded_&]:hidden" />
            </h2>

            <ul class="flex flex-col gap-1">
                @foreach ($menu as $item)
                    @php
                        $active = request()->routeIs($item['active'] ?? $item['route']);
                    @endphp
                    <li>
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'menu-item group xl:justify-center xl:[.sidebar-expanded_&]:justify-start',
                               'menu-item-active' => $active,
                               'menu-item-inactive' => ! $active,
                           ])
                           @if ($active) aria-current="page" @endif
                           title="{{ __($item['title']) }}">
                            <x-icon :name="$item['icon']" :class="$active ? 'menu-item-icon-active' : 'menu-item-icon-inactive'" />
                            <span class="xl:hidden xl:[.sidebar-expanded_&]:inline">{{ __($item['title']) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</aside>
