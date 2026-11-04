<?php

namespace App\Support\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * Sorts by a column of a belongs-to table through a subquery (no join, so the selected columns stay clean).
 * Example: new RelatedColumnSort('users', 'name', 'user_id') sorts employees by their user's name.
 */
class RelatedColumnSort implements Sort
{
    public function __construct(
        private readonly string $table,
        private readonly string $column,
        private readonly string $foreignKey,
    ) {}

    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderBy(
            DB::table($this->table)
                ->select($this->column)
                ->whereColumn("{$this->table}.id", $query->qualifyColumn($this->foreignKey))
                ->limit(1),
            $descending ? 'desc' : 'asc',
        );
    }
}
