<?php

use App\Enums\LeaveStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Js;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

// Today is Wednesday 2026-11-04; the default work week has Sunday off.
beforeEach(fn () => travelTo(Carbon::parse('2026-11-04 10:00')));

/** Names on the calendar, per Y-m-d day: ['2026-11-09' => ['Sam Sales'], …]. */
function calendarDays(TestResponse $response): array
{
    return collect($response->viewData('weeks'))->flatten(1)
        ->filter(fn (array $day) => $day['leaves']->isNotEmpty())
        ->mapWithKeys(fn (array $day) => [$day['date']->toDateString() => $day['leaves']->pluck('user.name')->all()])
        ->all();
}

/** Sales: manager approved + Sam pending (+ Sam rejected); Support: Alex and HR approved. */
function calendarPeople(): array
{
    $people = approvalPeople();
    $type = LeaveType::factory()->create(['name' => 'Casual Leave']);

    bookedLeave($people['manager'], $type, '2026-11-09', null, 1);
    bookedLeave($people['sales employee'], $type, '2026-11-10', null, 1, LeaveStatus::Pending)->update(['reason' => 'Private reason']);
    bookedLeave($people['sales employee'], $type, '2026-11-16', null, 1, LeaveStatus::Rejected);
    bookedLeave($people['support employee'], $type, '2026-11-11', null, 1);
    bookedLeave($people['hr'], $type, '2026-11-12', null, 1);

    return $people;
}

test('each role sees the leave of the departments it belongs to or manages, everyone for HR and admin', function (string $viewer, array $visible) {
    $people = calendarPeople();

    $response = actingAs($people[$viewer])->get(route('leaves.calendar'))
        ->assertOk()
        ->assertSeeText('November 2026')
        ->assertDontSee('Private reason');

    expect(collect(calendarDays($response))->flatten()->unique()->sort()->values()->all())
        ->toBe(collect($visible)->map(fn (string $key) => $people[$key]->name)->sort()->values()->all());
})->with([
    'sales employee' => ['sales employee', ['manager', 'sales employee']],
    'sales manager' => ['manager', ['manager', 'sales employee']],
    'support employee' => ['support employee', ['support employee', 'hr']],
    'HR' => ['hr', ['manager', 'sales employee', 'support employee', 'hr']],
    'admin' => ['admin', ['manager', 'sales employee', 'support employee', 'hr']],
]);

test('HR and admin can filter by department; others can not widen their view', function () {
    $people = calendarPeople();
    $support = Department::firstWhere('name', 'Support');

    $hrView = actingAs($people['hr'])->get(route('leaves.calendar', ['department' => $support->id]))
        ->assertSee('<option value="'.$support->id.'" selected>Support</option>', false);
    expect(array_keys(calendarDays($hrView)))->toBe(['2026-11-11', '2026-11-12']);

    $employeeView = actingAs($people['sales employee'])->get(route('leaves.calendar', ['department' => $support->id]))
        ->assertDontSee('All departments');
    expect(array_keys(calendarDays($employeeView)))->toBe(['2026-11-09', '2026-11-10']);
});

test('the grid runs Monday to Sunday, skips off days and shows leave in every month it touches', function () {
    $employee = userWithRole('Employee');
    Employee::factory()->for($employee)->create();
    $type = LeaveType::factory()->create();
    bookedLeave($employee, $type, '2026-11-14', '2026-11-16', 2);   // Sat – Mon, Sunday off
    bookedLeave($employee, $type, '2026-11-30', '2026-12-02', 3);   // Mon – Wed

    $november = actingAs($employee)->get(route('leaves.calendar'));
    $weeks = $november->viewData('weeks');

    expect($weeks[0][0]['date']->toDateString())->toBe('2026-10-26')
        ->and(end($weeks)[6]['date']->toDateString())->toBe('2026-12-06')
        ->and(array_keys(calendarDays($november)))->toBe(['2026-11-14', '2026-11-16', '2026-11-30', '2026-12-01', '2026-12-02'])
        ->and(collect($weeks)->flatten(1)->firstWhere('today')['date']->toDateString())->toBe('2026-11-04');

    $december = actingAs($employee)->get(route('leaves.calendar', ['month' => '2026-12']))->assertSeeText('December 2026');
    expect(array_keys(calendarDays($december)))->toBe(['2026-11-30', '2026-12-01', '2026-12-02']);

    actingAs($employee)->get(route('leaves.calendar', ['month' => '2026-13']))->assertSeeText('November 2026');
});

test('more names than fit in a day collapse into "+N more"', function () {
    $people = approvalPeople();
    $type = LeaveType::factory()->create();
    $sales = Department::firstWhere('name', 'Sales');

    foreach (range(1, 4) as $i) {
        $colleague = User::factory()->create(['name' => "Colleague {$i}"]);
        Employee::factory()->for($colleague)->for($sales)->create();
        bookedLeave($colleague, $type, '2026-11-09', null, 1);
    }

    actingAs($people['manager'])->get(route('leaves.calendar'))
        ->assertSeeText('+1 more')
        ->assertDontSeeText('Colleague 4 ½');
});

test('clicking a day lists everyone off with code, department and role', function () {
    $people = calendarPeople();
    $code = $people['manager']->employee->employee_code;

    $response = actingAs($people['sales employee'])->get(route('leaves.calendar'))
        ->assertSee('@click="day = '.e(Js::from('2026-11-09')).'; show = true"', false);

    expect($response->viewData('details')['2026-11-09'])->toBe([
        'title' => 'Monday, 09 Nov 2026',
        'leaves' => [[
            'name' => $people['manager']->name,
            'details' => "{$code} · Sales · Manager",
            'type' => 'Casual Leave',
            'period' => '09 Nov 2026',
            'status' => 'Approved',
            'pending' => false,
        ]],
    ])->and(json_encode($response->viewData('details')))->not->toContain('Private reason');
});
