<x-app-layout>
    <x-slot name="header">{{ $department->exists ? 'Edit department' : 'Add department' }}</x-slot>

    <div class="max-w-2xl rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
        <form method="POST" class="space-y-5" novalidate x-data="{ busy: false }" @submit="busy = true" action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}">
            @csrf
            @if ($department->exists)
                @method('PUT')
            @endif

            <div>
                <x-input-label for="name">Name<span class="text-error-500">*</span></x-input-label>
                <x-text-input id="name" name="name" :value="old('name', $department->name)" required maxlength="100" autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="manager_id" value="Manager" />
                <x-select-input id="manager_id" name="manager_id">
                    <option value="">No manager</option>
                    @foreach ($managers as $manager)
                        <option value="{{ $manager->id }}" @selected((string) old('manager_id', $department->manager_id) === (string) $manager->id)>{{ $manager->name }}</option>
                    @endforeach
                </x-select-input>
                <p class="mt-1.5 text-theme-xs text-gray-500">Approves leave for this department. Only active users with the Manager role are listed.</p>
                <x-input-error :messages="$errors->get('manager_id')" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button x-bind:disabled="busy">
                    <span x-text="busy ? 'Saving…' : 'Save'">Save</span>
                </x-primary-button>
                <x-secondary-button :href="route('departments.index')">Cancel</x-secondary-button>
            </div>
        </form>
    </div>
</x-app-layout>
