<x-app-layout>
    <x-slot name="header">{{ $leaveType->exists ? 'Edit leave type' : 'Add leave type' }}</x-slot>

    <div class="max-w-2xl rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
        <form method="POST" class="space-y-5" novalidate x-data="{ busy: false }" @submit="busy = true" action="{{ $leaveType->exists ? route('leave-types.update', $leaveType) : route('leave-types.store') }}">
            @csrf
            @if ($leaveType->exists)
                @method('PUT')
            @endif

            <div>
                <x-input-label for="name">Name<span class="text-error-500">*</span></x-input-label>
                <x-text-input id="name" name="name" :value="old('name', $leaveType->name)" required maxlength="50" autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="yearly_quota" value="Yearly quota (days)" />
                <x-text-input id="yearly_quota" name="yearly_quota" type="number" min="1" max="365" :value="old('yearly_quota', $leaveType->yearly_quota)" />
                <p class="mt-1.5 text-theme-xs text-gray-500">Leave empty for unlimited (e.g. unpaid leave).</p>
                <x-input-error :messages="$errors->get('yearly_quota')" />
            </div>

            <div>
                <label for="is_active" class="flex cursor-pointer items-center gap-3 text-sm text-gray-700 select-none">
                    <input id="is_active" type="checkbox" name="is_active" value="1" class="size-4 rounded border-gray-300 accent-brand-500" @checked(old('is_active', $leaveType->is_active))>
                    Active
                </label>
                <p class="mt-1.5 text-theme-xs text-gray-500">Inactive types can't be chosen for new leave requests.</p>
                <x-input-error :messages="$errors->get('is_active')" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button x-bind:disabled="busy">
                    <span x-text="busy ? 'Saving…' : 'Save'">Save</span>
                </x-primary-button>
                <x-secondary-button :href="route('leave-types.index')">Cancel</x-secondary-button>
            </div>
        </form>
    </div>
</x-app-layout>
