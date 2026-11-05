<?php

namespace App\Http\Queries;

use App\Models\LeaveRequest;
use App\Support\Sorts\LeaveSorts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Leave report: everyone's leave in any status, filtered by period, type, status and department.
 * Shared by the report page (the real request) and the CSV export job (`new Request(['filter' => $export->filters])`),
 * so the file always matches the screen.
 */
final class LeaveReportQuery
{
    public static function for(Request $request): QueryBuilder
    {
        $byFrom = LeaveSorts::from();

        return QueryBuilder::for(LeaveRequest::with(['leaveType', 'actionedBy', 'user.employee.department']), $request)
            ->allowedFilters([
                // The period keeps every leave that overlaps it; an invalid date is ignored like an empty one.
                AllowedFilter::callback('from', fn (Builder $query, $value) => ($date = self::date($value)) ? $query->endingFrom($date) : $query),
                AllowedFilter::callback('to', fn (Builder $query, $value) => ($date = self::date($value)) ? $query->startingBy($date) : $query),
                AllowedFilter::exact('type', 'leave_type_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('department', fn (Builder $query, $value) => $query->inDepartment((int) $value)),
            ])
            ->allowedSorts([$byFrom, ...LeaveSorts::columns(withEmployee: true)])
            ->defaultSort($byFrom)
            ->orderBy('leave_requests.id');
    }

    /** A Y-m-d filter value as a start-of-day date; anything else (incl. 2026-02-31) is null. */
    public static function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $date = Carbon::createFromFormat('!Y-m-d', $value);

        return $date->format('Y-m-d') === $value ? $date : null;
    }
}
