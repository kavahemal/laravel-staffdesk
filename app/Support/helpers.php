<?php

use App\Services\SettingService;
use Carbon\CarbonInterface;

if (! function_exists('format_date')) {
    /** Format a date for display in the format chosen on the Settings page; empty values become an em dash. */
    function format_date(?CarbonInterface $date): string
    {
        return $date?->format(app(SettingService::class)->dateFormat()) ?? '—';
    }
}

if (! function_exists('format_days')) {
    /** Leave days for display: 3 → "3", 2.5 → "2.5". */
    function format_days(float|int|null $days): string
    {
        return $days === null ? '—' : rtrim(rtrim(number_format((float) $days, 1, '.', ''), '0'), '.');
    }
}
