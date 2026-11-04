<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\WorkWeek;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const WORK_WEEK = 'work_week';

    /** The saved work week, or the default from `config/hrms.php`. */
    public function workWeek(): WorkWeek
    {
        return WorkWeek::fromArray($this->get(self::WORK_WEEK) ?? config('hrms.work_week'));
    }

    public function saveWorkWeek(WorkWeek $workWeek): void
    {
        $this->set(self::WORK_WEEK, $workWeek->toArray());
    }

    private function get(string $key): mixed
    {
        // Wrapped so a missing setting is cached too (a cached null counts as a miss).
        return Cache::rememberForever(
            "settings.{$key}",
            fn () => ['value' => Setting::firstWhere('key', $key)?->value],
        )['value'];
    }

    private function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget("settings.{$key}");
    }
}
