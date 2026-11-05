@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn(string $part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp

<header class="sticky top-0 z-40 flex w-full border-b border-gray-200 bg-white">
    <div class="flex grow items-center justify-between gap-2 px-3 py-3 sm:gap-4 xl:px-6 xl:py-4">
        <div class="flex items-center gap-2 sm:gap-4">
            {{-- Desktop: collapse / expand --}}
            <button type="button" @click.stop="$store.sidebar.toggleExpanded()" aria-label="Toggle sidebar" class="hidden size-11 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 xl:inline-flex">
                <x-heroicon-o-bars-3 class="size-6" />
            </button>

            {{-- Mobile: open / close overlay --}}
            <button type="button" @click.stop="$store.sidebar.toggleMobileOpen()" aria-label="Toggle sidebar" class="inline-flex size-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 xl:hidden">
                <x-heroicon-o-x-mark class="size-6" x-cloak x-show="$store.sidebar.isMobileOpen" />
                <x-heroicon-o-bars-3 class="size-6" x-show="! $store.sidebar.isMobileOpen" />
            </button>

            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 xl:hidden">
                <x-application-logo />
                <span class="text-lg font-semibold text-gray-800">{{ $companyName }}</span>
            </a>
        </div>

        <div class="flex items-center gap-3">
            {{-- Asks for new notifications every few seconds while the tab is visible; stops once the session is gone. --}}
            <div x-data="{
                unread: @js($unreadCount),
                timer: null,
                init() {
                    this.timer = setInterval(() => document.hidden || this.refresh(), @js(config('hrms.notification_poll_seconds') * 1000));
                },
                async refresh() {
                    const response = await fetch(@js(route('notifications.bell')), { headers: { Accept: 'application/json' } }).catch(() => null);
                    if (!response) return;
                    if (!response.ok || response.redirected) return clearInterval(this.timer);

                    const { unread, html } = await response.json();
                    if (unread > this.unread) toast('success', 'You have a new notification.');
                    this.unread = unread;
                    this.$el.querySelector('[data-notification-list]').innerHTML = html;
                },
            }" @visibilitychange.document="document.hidden || refresh()">
                <x-dropdown align="right" width="w-80 sm:w-96" contentClasses="p-0">
                    <x-slot name="trigger">
                        <button type="button" title="Notifications" class="relative flex size-11 items-center justify-center rounded-full border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                            <x-heroicon-o-bell class="size-5" />
                            <span x-show="unread > 0" x-text="unread > 9 ? '9+' : unread" @style(['display: none' => $unreadCount === 0]) class="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-error-500 px-1 text-theme-xs font-medium text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div data-notification-list>
                            @include('layouts.notifications')
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            <x-dropdown align="right" width="w-65">
                <x-slot name="trigger">
                    <button type="button" class="flex items-center gap-3 text-gray-700">
                        <span class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-500">{{ $initials }}</span>
                        <span class="hidden text-sm font-medium sm:block">{{ $user->name }}</span>
                        <x-heroicon-o-chevron-down class="hidden size-5 text-gray-500 transition-transform duration-200 sm:block" x-bind:class="open && 'rotate-180'" />
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-3 py-2">
                        <span class="block text-sm font-medium text-gray-700">{{ $user->name }}</span>
                        <span class="mt-0.5 block text-theme-xs text-gray-500">{{ $user->email }}</span>
                    </div>

                    <div class="-mx-3 my-1.5 h-px bg-gray-100"></div>

                    <x-dropdown-link :href="route('profile.edit')">
                        <x-heroicon-o-user-circle />
                        Edit profile
                    </x-dropdown-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link as="button" type="submit">
                            <x-heroicon-o-arrow-right-start-on-rectangle />
                            Sign out
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>
