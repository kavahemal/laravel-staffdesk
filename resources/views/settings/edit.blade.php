<x-app-layout>
    <x-slot name="header">Settings</x-slot>

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6" novalidate x-data="{ busy: false }" @submit="busy = true">
        @csrf
        @method('PUT')

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
                <h3 class="text-base font-medium text-gray-800">General</h3>

                <div>
                    <x-input-label for="company_name">Company name<span class="text-error-500">*</span></x-input-label>
                    <x-text-input id="company_name" name="company_name" :value="old('company_name', $companyName)" required maxlength="50" />
                    <p class="mt-1.5 text-theme-xs text-gray-500">Shown in the sidebar, the login page and the browser tab.</p>
                    <x-input-error :messages="$errors->get('company_name')" />
                </div>

                <div>
                    <x-input-label for="logo" value="Logo" />
                    <div class="flex items-center gap-4">
                        <x-application-logo class="size-11" />
                        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp" @class([
                            'h-11 w-full min-w-0 cursor-pointer overflow-hidden rounded-lg border bg-transparent text-sm leading-[2.625rem] text-gray-500 shadow-theme-xs transition-colors file:me-4 file:h-full file:cursor-pointer file:border-0 file:border-e file:border-solid file:border-gray-200 file:bg-gray-50 file:px-3.5 file:py-0 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-100 focus:ring-3 focus:outline-hidden',
                            'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10' => ! $errors->has('logo'),
                            'border-error-500 focus:border-error-300 focus:ring-error-500/10' => $errors->has('logo'),
                        ]) @if ($errors->has('logo')) aria-invalid="true" @endif>
                    </div>
                    <p class="mt-1.5 text-theme-xs text-gray-500">PNG, JPG or WebP up to {{ config('hrms.logo_max_kb') / 1024 }} MB; a square image works best. Also used as the browser tab icon.</p>
                    @if ($hasLogo)
                        <label class="mt-2 flex items-center gap-2 text-theme-sm text-gray-700">
                            <input type="checkbox" name="remove_logo" value="1" class="size-4 rounded border-gray-300 text-brand-500" @checked(old('remove_logo'))>
                            Remove logo and use the default
                        </label>
                    @endif
                    <x-input-error :messages="$errors->get('logo')" />
                </div>

                <div>
                    <x-input-label for="date_format">Date format<span class="text-error-500">*</span></x-input-label>
                    <x-select-input id="date_format" name="date_format">
                        @foreach ($dateFormats as $format)
                            <option value="{{ $format }}" @selected(old('date_format', $dateFormat) === $format)>{{ now()->format($format) }}</option>
                        @endforeach
                    </x-select-input>
                    <p class="mt-1.5 text-theme-xs text-gray-500">Used for every date in lists, date pickers and CSV exports.</p>
                    <x-input-error :messages="$errors->get('date_format')" />
                </div>
            </div>

            <div class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 lg:p-6">
                <div>
                    <h3 class="text-base font-medium text-gray-800">Work week</h3>
                    <p class="mt-1 text-theme-sm text-gray-500">
                        How each weekday counts for leave: a full day counts 1, a half day 0.5, an off day 0.
                        Changes apply to new leave requests only.
                    </p>
                    <x-input-error :messages="$errors->get('work_week')" class="mt-2" />
                </div>

                <div class="divide-y divide-gray-100 rounded-xl border border-gray-200">
                    @foreach ($dayNames as $day => $dayName)
                        <div class="flex flex-col gap-2 px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                            <label for="work_week_{{ $day }}" class="text-sm font-medium text-gray-700">{{ $dayName }}</label>
                            <div class="sm:w-48">
                                <x-select-input id="work_week_{{ $day }}" name="work_week[{{ $day }}]">
                                    @foreach ($options as $option)
                                        <option value="{{ $option->value }}" @selected(old("work_week.{$day}", $workWeek->day($day)->value) === $option->value)>{{ $option->label() }}</option>
                                    @endforeach
                                </x-select-input>
                                <x-input-error :messages="$errors->get('work_week.' . $day)" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 lg:col-span-2 lg:p-6" x-data="{ mailer: @js(old('mail_mailer', $mail['mailer'])) }">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-medium text-gray-800">Email</h3>
                        <p class="mt-1 text-theme-sm text-gray-500">Used for password reset emails. Save first, then send a test email to {{ auth()->user()->email }}.</p>
                    </div>
                    <x-secondary-button type="submit" form="test-email-form">
                        <x-heroicon-o-paper-airplane class="size-5" />
                        Send test email
                    </x-secondary-button>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <x-input-label for="mail_mailer">Send emails<span class="text-error-500">*</span></x-input-label>
                        <x-select-input id="mail_mailer" name="mail_mailer" x-model="mailer">
                            <option value="log" @selected(old('mail_mailer', $mail['mailer']) === 'log')>Off – write them to the log file</option>
                            <option value="smtp" @selected(old('mail_mailer', $mail['mailer']) === 'smtp')>Through an SMTP server</option>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('mail_mailer')" />
                    </div>

                    <div>
                        <x-input-label for="mail_from_address">From address<span class="text-error-500">*</span></x-input-label>
                        <x-text-input id="mail_from_address" name="mail_from_address" type="email" :value="old('mail_from_address', $mail['from_address'])" required maxlength="255" />
                        <x-input-error :messages="$errors->get('mail_from_address')" />
                    </div>

                    <div>
                        <x-input-label for="mail_from_name" value="From name" />
                        <x-text-input id="mail_from_name" name="mail_from_name" :value="old('mail_from_name', $mail['from_name'])" maxlength="100" :placeholder="$companyName" />
                        <x-input-error :messages="$errors->get('mail_from_name')" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3" x-show="mailer === 'smtp'" x-cloak>
                    <div>
                        <x-input-label for="mail_host">Host<span class="text-error-500">*</span></x-input-label>
                        <x-text-input id="mail_host" name="mail_host" :value="old('mail_host', $mail['host'])" maxlength="255" placeholder="smtp.example.com" />
                        <x-input-error :messages="$errors->get('mail_host')" />
                    </div>

                    <div>
                        <x-input-label for="mail_port">Port<span class="text-error-500">*</span></x-input-label>
                        <x-text-input id="mail_port" name="mail_port" type="number" min="1" max="65535" :value="old('mail_port', $mail['port'])" />
                        <x-input-error :messages="$errors->get('mail_port')" />
                    </div>

                    <div>
                        <x-input-label for="mail_scheme">Encryption<span class="text-error-500">*</span></x-input-label>
                        <x-select-input id="mail_scheme" name="mail_scheme">
                            <option value="smtp" @selected(old('mail_scheme', $mail['scheme']) === 'smtp')>STARTTLS when offered (port 587, 25)</option>
                            <option value="smtps" @selected(old('mail_scheme', $mail['scheme']) === 'smtps')>SSL/TLS (port 465)</option>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('mail_scheme')" />
                    </div>

                    <div>
                        <x-input-label for="mail_username" value="Username" />
                        <x-text-input id="mail_username" name="mail_username" :value="old('mail_username', $mail['username'])" maxlength="255" autocomplete="off" />
                        <x-input-error :messages="$errors->get('mail_username')" />
                    </div>

                    <div>
                        <x-input-label for="mail_password" value="Password" />
                        <x-password-input id="mail_password" name="mail_password" maxlength="255" autocomplete="new-password"
                            :placeholder="$mail['has_password'] ? 'Saved – leave blank to keep it' : ''" />
                        <p class="mt-1.5 text-theme-xs text-gray-500">Stored encrypted. Clear the username to remove it.</p>
                        <x-input-error :messages="$errors->get('mail_password')" />
                    </div>
                </div>
            </div>
        </div>

        <x-primary-button x-bind:disabled="busy">
            <span x-text="busy ? 'Saving…' : 'Save'">Save</span>
        </x-primary-button>
    </form>

    {{-- Outside the settings form (forms can't be nested); the button in the Email card submits it. --}}
    <form id="test-email-form" method="POST" action="{{ route('settings.test-email') }}">
        @csrf
    </form>
</x-app-layout>
