<x-app-layout>
    <x-slot name="header">{{ $employee->exists ? "Edit employee {$employee->employee_code}" : 'Add employee' }}</x-slot>

    @php
        $currentRole = old('role', $employee->user?->roles->first()?->name);
        $currentDepartment = (string) old('department_id', $employee->department_id);
    @endphp

    <div class="max-w-4xl rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
        <form method="POST" novalidate x-data="{ busy: false }" @submit="busy = true" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
            @csrf
            @if ($employee->exists)
                @method('PUT')
            @endif

            <h2 class="mb-4 text-lg font-semibold text-gray-800">Account</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="name">Name<span class="text-error-500">*</span></x-input-label>
                    <x-text-input id="name" name="name" :value="old('name', $employee->user?->name)" required autofocus autocomplete="off" />
                    <x-input-error :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="email">Email<span class="text-error-500">*</span></x-input-label>
                    <x-text-input id="email" name="email" type="email" :value="old('email', $employee->user?->email)" required autocomplete="off" />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <div>
                    <x-input-label for="role">Role<span class="text-error-500">*</span></x-input-label>
                    <x-select-input id="role" name="role" required>
                        <option value="" disabled @selected(!$currentRole)>Select a role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected($currentRole === $role)>{{ $role }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('role')" />
                </div>

                @unless ($employee->exists)
                    <div class="hidden sm:block"></div>

                    <div>
                        <x-input-label for="password">Initial password<span class="text-error-500">*</span></x-input-label>
                        <x-password-input id="password" name="password" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation">Confirm password<span class="text-error-500">*</span></x-input-label>
                        <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
                    </div>
                @endunless
            </div>

            <h2 class="mt-8 mb-4 text-lg font-semibold text-gray-800">Employment</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="department_id">Department<span class="text-error-500">*</span></x-input-label>
                    <x-select-input id="department_id" name="department_id" required>
                        <option value="" disabled @selected($currentDepartment === '')>Select a department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected($currentDepartment === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('department_id')" />
                </div>

                <div>
                    <x-input-label for="designation">Designation<span class="text-error-500">*</span></x-input-label>
                    <x-text-input id="designation" name="designation" :value="old('designation', $employee->designation)" required maxlength="100" />
                    <x-input-error :messages="$errors->get('designation')" />
                </div>

                <div>
                    <x-input-label for="joining_date">Joining date<span class="text-error-500">*</span></x-input-label>
                    <x-date-input id="joining_date" name="joining_date" :value="old('joining_date', $employee->joining_date?->format('Y-m-d'))" required />
                    <x-input-error :messages="$errors->get('joining_date')" />
                </div>

                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" type="tel" :value="old('phone', $employee->phone)" maxlength="20" />
                    <x-input-error :messages="$errors->get('phone')" />
                </div>
            </div>

            <div class="mt-8 flex items-center gap-3">
                <x-primary-button x-bind:disabled="busy">
                    <span x-text="busy ? 'Saving…' : 'Save'">Save</span>
                </x-primary-button>
                <x-secondary-button :href="route('employees.index')">Cancel</x-secondary-button>
            </div>
        </form>
    </div>
</x-app-layout>
