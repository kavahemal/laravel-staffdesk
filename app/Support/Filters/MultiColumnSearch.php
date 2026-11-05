<?php

namespace App\Support\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * List search over several columns (OR); "relation.column" searches a related model, one whereHas per relation.
 * One column only? Use AllowedFilter::partial('search', 'name') instead.
 * Usage: AllowedFilter::custom('search', new MultiColumnSearch(['employee_code', 'user.name', 'user.email']))
 */
class MultiColumnSearch implements Filter
{
    /** @param  list<string>  $columns */
    public function __construct(private readonly array $columns) {}

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $like = $this->likePattern($value);

        $byRelation = collect($this->columns)->groupBy(fn (string $column) => Str::contains($column, '.') ? Str::beforeLast($column, '.') : '');

        $query->where(function (Builder $query) use ($byRelation, $like) {
            foreach ($byRelation as $relation => $columns) {
                $match = fn (Builder $query) => $columns->each(fn (string $column) => $query->orWhere(Str::afterLast($column, '.'), 'like', $like));

                $relation === '' ? $match($query) : $query->orWhereHas($relation, fn (Builder $related) => $related->where($match));
            }
        });
    }

    /**
     * `%term%` with `%`, `_` and `\` matched literally. Query Builder splits filter values on commas,
     * so they are joined back ("Smith, John" stays one term); the length is capped.
     */
    private function likePattern(mixed $value): string
    {
        $term = Str::limit(implode(',', (array) $value), config('staffdesk.search_max_length'), '');

        return '%'.addcslashes($term, '%_\\').'%';
    }
}
