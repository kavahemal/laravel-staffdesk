<?php

use App\Models\Setting;
use App\Services\LeaveService;
use App\Services\SettingService;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;

function workWeekInput(array $changes = []): array
{
    return ['work_week' => array_replace(
        [1 => 'full', 2 => 'full', 3 => 'full', 4 => 'full', 5 => 'full', 6 => 'full', 0 => 'off'],
        $changes,
    )];
}

test('admin sees the work week, defaulting to Monday to Saturday', function () {
    actingAs(userWithRole('Admin'))->get(route('settings.edit'))
        ->assertOk()
        ->assertSeeInOrder(['Monday', 'Saturday', 'Sunday'])
        ->assertSee('<option value="off" selected>', false);
});

test('admin saves the work week and leave is counted with it', function () {
    actingAs(userWithRole('Admin'))
        ->put(route('settings.update'), workWeekInput([6 => 'half']))
        ->assertRedirect(route('settings.edit'))
        ->assertSessionHas('success', 'Settings saved.');

    expect(Setting::sole()->value[6])->toBe('half')
        ->and(app(SettingService::class)->workWeek()->toArray()[6])->toBe('half')
        // Mon 9 – Sun 15 Nov 2026 with a half Saturday.
        ->and(app(LeaveService::class)->countDays(Carbon::parse('2026-11-09'), Carbon::parse('2026-11-15')))->toBe(5.5);

    actingAs(userWithRole('Admin'))->get(route('settings.edit'))->assertSee('<option value="half" selected>', false);
});

test('work week input is validated', function (array $input, string $field) {
    actingAs(userWithRole('Admin'))->put(route('settings.update'), $input)->assertSessionHasErrors($field);

    expect(Setting::count())->toBe(0);
})->with([
    'unknown value' => [workWeekInput([3 => 'sometimes']), 'work_week.3'],
    'missing day' => [['work_week' => [1 => 'full']], 'work_week.2'],
    'every day off' => [workWeekInput(array_fill(0, 7, 'off')), 'work_week'],
]);

test('users without settings.manage get 403', function (string $role) {
    $user = userWithRole($role);

    actingAs($user)->get(route('settings.edit'))->assertForbidden();
    actingAs($user)->put(route('settings.update'), workWeekInput())->assertForbidden();
})->with(['HR', 'Manager', 'Employee']);
