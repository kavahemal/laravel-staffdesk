<?php

use App\Services\LeaveService;
use App\Support\WorkWeek;
use Illuminate\Support\Carbon;

// 2026-11-09 is a Monday, 2026-11-14 a Saturday, 2026-11-15 a Sunday.

function countLeaveDays(array $workWeek, string $from, string $to): float
{
    return (new LeaveService(WorkWeek::fromArray($workWeek)))->countDays(Carbon::parse($from), Carbon::parse($to));
}

const MON_TO_SAT = [1 => 'full', 2 => 'full', 3 => 'full', 4 => 'full', 5 => 'full', 6 => 'full', 0 => 'off'];

test('days are counted inclusively without off days', function (string $from, string $to, float $days) {
    expect(countLeaveDays(MON_TO_SAT, $from, $to))->toBe($days);
})->with([
    'single weekday' => ['2026-11-09', '2026-11-09', 1.0],
    'Monday to Saturday' => ['2026-11-09', '2026-11-14', 6.0],
    'full week' => ['2026-11-09', '2026-11-15', 6.0],
    'Saturday over Sunday to Monday' => ['2026-11-14', '2026-11-16', 2.0],
    'two weeks' => ['2026-11-09', '2026-11-22', 12.0],
    'only a Sunday' => ['2026-11-15', '2026-11-15', 0.0],
    'to before from' => ['2026-11-10', '2026-11-09', 0.0],
    'times are ignored' => ['2026-11-09 18:00', '2026-11-10 08:00', 2.0],
]);

test('a half working day counts 0.5 and every off day is skipped', function () {
    $halfSaturday = array_replace(MON_TO_SAT, [6 => 'half']);
    $fiveDayWeek = array_replace(MON_TO_SAT, [6 => 'off']);

    expect(countLeaveDays($halfSaturday, '2026-11-09', '2026-11-15'))->toBe(5.5)
        ->and(countLeaveDays($halfSaturday, '2026-11-14', '2026-11-14'))->toBe(0.5)
        ->and(countLeaveDays($fiveDayWeek, '2026-11-09', '2026-11-15'))->toBe(5.0);
});

test('a missing or unknown weekday value counts as off', function () {
    expect(countLeaveDays([1 => 'full', 2 => 'bogus'], '2026-11-09', '2026-11-15'))->toBe(1.0);
});
