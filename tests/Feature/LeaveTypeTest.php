<?php

use App\Models\LeaveRequest;
use App\Models\LeaveType;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

test('admin sees leave types sorted by name, with quota and status', function () {
    LeaveType::factory()->create(['name' => 'Sick Leave', 'yearly_quota' => 7]);
    LeaveType::factory()->unlimited()->inactive()->create(['name' => 'Annual Unpaid']);

    actingAs(userWithRole('Admin'))->get(route('leave-types.index'))
        ->assertOk()
        ->assertSeeInOrder(['Annual Unpaid', 'Unlimited', 'Inactive', 'Sick Leave', '7 days', 'Active']);
});

test('the list shows an empty state, searches and sorts by quota', function () {
    $admin = userWithRole('Admin');

    actingAs($admin)->get(route('leave-types.index'))->assertSeeText('No leave types yet.');

    LeaveType::factory()->create(['name' => 'Casual Leave', 'yearly_quota' => 10]);
    LeaveType::factory()->create(['name' => 'Sick Leave', 'yearly_quota' => 5]);

    actingAs($admin)->get(route('leave-types.index', ['filter' => ['search' => 'sick']]))
        ->assertSeeText('Sick Leave')->assertDontSeeText('Casual Leave');
    actingAs($admin)->get(route('leave-types.index', ['sort' => 'quota']))
        ->assertSeeInOrder(['Sick Leave', 'Casual Leave']);
    actingAs($admin)->get(route('leave-types.index', ['sort' => 'is_active']))->assertBadRequest();
});

test('admin creates a leave type; an empty quota means unlimited', function () {
    actingAs(userWithRole('Admin'))
        ->post(route('leave-types.store'), ['name' => 'Unpaid Leave', 'yearly_quota' => '', 'is_active' => '1'])
        ->assertRedirect(route('leave-types.index'))
        ->assertSessionHas('success');

    expect(LeaveType::sole())
        ->name->toBe('Unpaid Leave')
        ->yearly_quota->toBeNull()
        ->is_active->toBeTrue();
});

test('leave type input is validated', function (array $data, string $field) {
    LeaveType::factory()->create(['name' => 'Sick Leave']);

    actingAs(userWithRole('Admin'))
        ->post(route('leave-types.store'), [...['name' => 'Casual Leave', 'yearly_quota' => 10], ...$data])
        ->assertSessionHasErrors($field);
})->with([
    'name required' => [['name' => ''], 'name'],
    'name unique' => [['name' => 'Sick Leave'], 'name'],
    'quota not a number' => [['yearly_quota' => 'ten'], 'yearly_quota'],
    'quota zero' => [['yearly_quota' => 0], 'yearly_quota'],
    'quota above a year' => [['yearly_quota' => 366], 'yearly_quota'],
]);

test('admin updates a leave type, keeps its name and can deactivate it (unticked box)', function () {
    $leaveType = LeaveType::factory()->create(['name' => 'Sick Leave', 'yearly_quota' => 7]);

    actingAs(userWithRole('Admin'))
        ->put(route('leave-types.update', $leaveType), ['name' => 'Sick Leave', 'yearly_quota' => 10])
        ->assertRedirect(route('leave-types.index'));

    expect($leaveType->fresh())
        ->yearly_quota->toBe(10)
        ->is_active->toBeFalse();
});

test('admin deletes an unused leave type', function () {
    $leaveType = LeaveType::factory()->create();

    actingAs(userWithRole('Admin'))->delete(route('leave-types.destroy', $leaveType))->assertSessionHas('success');

    assertModelMissing($leaveType);
});

test('a leave type used by leave requests can not be deleted', function () {
    $leaveType = LeaveType::factory()->create();
    LeaveRequest::factory()->for($leaveType)->create();

    actingAs(userWithRole('Admin'))->delete(route('leave-types.destroy', $leaveType))->assertSessionHas('error');

    expect($leaveType->fresh())->not->toBeNull();
});

test('users without leave-types.manage get 403 on every leave type page', function (string $role) {
    $user = userWithRole($role);
    $leaveType = LeaveType::factory()->create();

    actingAs($user)->get(route('leave-types.index'))->assertForbidden();
    actingAs($user)->get(route('leave-types.create'))->assertForbidden();
    actingAs($user)->post(route('leave-types.store'), ['name' => 'X'])->assertForbidden();
    actingAs($user)->get(route('leave-types.edit', $leaveType))->assertForbidden();
    actingAs($user)->put(route('leave-types.update', $leaveType), ['name' => 'X'])->assertForbidden();
    actingAs($user)->delete(route('leave-types.destroy', $leaveType))->assertForbidden();
})->with(['HR', 'Manager', 'Employee']);
