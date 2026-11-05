<?php

namespace App\Http\Requests;

use App\Enums\WorkDay;
use App\Support\WorkWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    public function rules(): array
    {
        $rules = [
            'company_name' => ['required', 'string', 'max:50'],
            'date_format' => ['required', Rule::in(config('staffdesk.date_formats'))],
            // No SVG: an SVG served from our own domain can run scripts.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:'.config('staffdesk.logo_max_kb')],
            'remove_logo' => ['boolean'],
            'mail_mailer' => ['required', Rule::in(['log', 'smtp'])],
            'mail_host' => ['nullable', 'required_if:mail_mailer,smtp', 'string', 'max:255'],
            'mail_port' => ['nullable', 'required_if:mail_mailer,smtp', 'integer', 'between:1,65535'],
            'mail_scheme' => ['nullable', 'required_if:mail_mailer,smtp', Rule::in(['smtp', 'smtps'])],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:100'],
            'work_week' => ['required', 'array'],
        ];

        foreach (WorkWeek::DISPLAY_ORDER as $day) {
            $rules["work_week.{$day}"] = ['required', Rule::enum(WorkDay::class)];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $values = (array) $this->input('work_week', []);

                if (! $validator->errors()->any() && ! in_array(WorkDay::Full->value, $values, true) && ! in_array(WorkDay::Half->value, $values, true)) {
                    $validator->errors()->add('work_week', 'At least one day must be a working day.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            ...collect(WorkWeek::dayNames())->mapWithKeys(fn (string $name, int $day) => ["work_week.{$day}" => $name])->all(),
            'mail_mailer' => 'send emails',
            'mail_host' => 'host',
            'mail_port' => 'port',
            'mail_scheme' => 'encryption',
            'mail_username' => 'username',
            'mail_password' => 'password',
            'mail_from_address' => 'from address',
            'mail_from_name' => 'from name',
        ];
    }

    public function messages(): array
    {
        return ['required_if' => 'The :attribute field is required to send emails through SMTP.'];
    }

    /** The validated mail_* fields without the prefix, for SettingService::saveMail(). */
    public function mailSettings(): array
    {
        return collect($this->validated())
            ->filter(fn ($value, string $key) => str_starts_with($key, 'mail_'))
            ->mapWithKeys(fn ($value, string $key) => [substr($key, 5) => $key === 'mail_port' && $value !== null ? (int) $value : $value])
            ->all();
    }
}
