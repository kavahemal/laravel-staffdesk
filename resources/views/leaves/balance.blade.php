<x-app-layout>
    <x-slot name="header">Leave balance {{ $year }}</x-slot>

    <x-slot name="actions">
        <x-primary-button :href="route('leaves.create')">
            <x-heroicon-o-plus />
            Apply leave
        </x-primary-button>
    </x-slot>

    <p class="mb-4 text-theme-sm text-gray-500">Pending requests are counted as used until they are decided.</p>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($balances as $balance)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
                <div class="flex items-start justify-between gap-3">
                    <h3 class="font-medium text-gray-800">{{ $balance->type->name }}</h3>
                    @unless ($balance->type->is_active)
                        <x-badge color="gray">Inactive</x-badge>
                    @endunless
                </div>

                <p class="mt-3 text-2xl font-semibold text-gray-800">
                    @if ($balance->remaining() === null)
                        Unlimited
                    @else
                        {{ format_days($balance->remaining()) }} <span class="text-theme-sm font-normal text-gray-500">days left</span>
                    @endif
                </p>

                @if ($balance->usedPercent() !== null)
                    <div class="mt-3 h-2 rounded-full bg-gray-100">
                        <div class="h-2 rounded-full bg-brand-500" style="width: {{ $balance->usedPercent() }}%"></div>
                    </div>
                @endif

                <dl class="mt-4 grid grid-cols-3 gap-2 text-theme-xs">
                    <div>
                        <dt class="text-gray-500">Quota</dt>
                        <dd class="mt-0.5 font-medium text-gray-800">{{ $balance->quota() === null ? '—' : $balance->quota() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Approved</dt>
                        <dd class="mt-0.5 font-medium text-gray-800">{{ format_days($balance->approved) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Pending</dt>
                        <dd class="mt-0.5 font-medium text-gray-800">{{ format_days($balance->pending) }}</dd>
                    </div>
                </dl>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-200 bg-white p-10 text-center text-gray-500 sm:col-span-2 xl:col-span-3">
                No leave types yet.
            </div>
        @endforelse
    </div>
</x-app-layout>
