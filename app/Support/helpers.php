<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

if (! function_exists('format_date')) {
    /** Format a date for display using `hrms.date_format`; empty values become an em dash. */
    function format_date(?CarbonInterface $date): string
    {
        return $date?->format(config('hrms.date_format')) ?? '—';
    }
}

if (! function_exists('format_days')) {
    /** Leave days for display: 3 → "3", 2.5 → "2.5". */
    function format_days(float|int|null $days): string
    {
        return $days === null ? '—' : rtrim(rtrim(number_format((float) $days, 1, '.', ''), '0'), '.');
    }
}

if (! function_exists('search_term')) {
    /** A list search value: Query Builder splits filter values on commas, so join them back ("Smith, John" stays one term) and cap the length. */
    function search_term(mixed $value): string
    {
        return Str::limit(implode(',', (array) $value), config('hrms.search_max_length'), '');
    }
}

if (! function_exists('like_pattern')) {
    /** `%term%` for a LIKE query, with `%`, `_` and `\` matched literally. */
    function like_pattern(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }
}
