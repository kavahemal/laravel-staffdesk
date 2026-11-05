<?php

namespace App\Support\Sorts;

use Spatie\QueryBuilder\AllowedSort;

/**
 * Sortable columns shared by the leave lists (My leaves, Leave requests, Leave report).
 * Usage: $byFrom = LeaveSorts::from(); ->allowedSorts([$byFrom, ...LeaveSorts::columns()])->defaultSort($byFrom)
 */
final class LeaveSorts
{
    /** Separate, so the same object can go to both allowedSorts() and defaultSort(). */
    public static function from(): AllowedSort
    {
        return AllowedSort::field('from', 'from_date');
    }

    /** @return list<AllowedSort> every sort except `from` */
    public static function columns(bool $withEmployee = false): array
    {
        return [
            ...($withEmployee ? [AllowedSort::custom('employee', new RelatedColumnSort('users', 'name', 'user_id'))] : []),
            AllowedSort::custom('type', new RelatedColumnSort('leave_types', 'name', 'leave_type_id')),
            AllowedSort::field('days'),
            AllowedSort::field('status'),
            AllowedSort::field('applied', 'created_at'),
        ];
    }
}
