<?php

use Carbon\CarbonInterface;

if (! function_exists('format_date')) {
    /** Format a date for display using `hrms.date_format`; empty values become an em dash. */
    function format_date(?CarbonInterface $date): string
    {
        return $date?->format(config('hrms.date_format')) ?? '—';
    }
}
