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
        $rules = ['work_week' => ['required', 'array']];

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
        return collect(WorkWeek::dayNames())->mapWithKeys(fn (string $name, int $day) => ["work_week.{$day}" => $name])->all();
    }
}
