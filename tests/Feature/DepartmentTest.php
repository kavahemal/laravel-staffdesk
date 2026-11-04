<?php

use App\Models\Department;
use App\Models\Employee;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;

test('admin sees the department list with manager and employee count', function () {
    $manager = userWithRole('Manager');
    $department = Department::factory()->withManager($manager)->create(['name' => 'Finance']);
    Employee::factory()->count(2)->for($department)->create();

    actingAs(userWithRole('Admin'))->get(route('departments.index'))
        ->assertOk()
        ->assertSeeInOrder(['Finance', $manager->name, '2']);
});

test('the list shows an empty state', function () {
    actingAs(userWithRole('Admin'))->get(route('departments.index'))->assertSeeText('No departments yet.');
});

test('admin creates a department with a manager', function () {
    $manager = userWithRole('Manager');

    actingAs(userWithRole('Admin'))
        ->post(route('departments.store'), ['name' => 'Finance', 'manager_id' => $manager->id])
        ->assertRedirect(route('departments.index'))
        ->assertSessionHas('success');

    expect(Department::sole())
        ->name->toBe('Finance')
        ->manager_id->toBe($manager->id);
});

test('name is required and unique, also against deleted departments', function () {
    Department::factory()->create(['name' => 'Finance'])->delete();
    $admin = userWithRole('Admin');

    actingAs($admin)->post(route('departments.store'), ['name' => ''])->assertSessionHasErrors('name');
    actingAs($admin)->post(route('departments.store'), ['name' => 'Finance'])->assertSessionHasErrors('name');
});

test('only an active user with the Manager role can be the manager', function (string $role, bool $active) {
    $candidate = userWithRole($role, ['is_active' => $active]);

    actingAs(userWithRole('Admin'))
        ->post(route('departments.store'), ['name' => 'Finance', 'manager_id' => $candidate->id])
        ->assertSessionHasErrors(['manager_id' => 'The manager must be an active user with the Manager role.']);
})->with([
    'employee' => ['Employee', true],
    'inactive manager' => ['Manager', false],
]);

test('admin updates a department and may keep its name', function () {
    $department = Department::factory()->create(['name' => 'Finance']);
    $manager = userWithRole('Manager');

    actingAs(userWithRole('Admin'))
        ->put(route('departments.update', $department), ['name' => 'Finance', 'manager_id' => $manager->id])
        ->assertRedirect(route('departments.index'));

    expect($department->fresh()->manager_id)->toBe($manager->id);
});

test('admin deletes an empty department (soft delete)', function () {
    $department = Department::factory()->create();

    actingAs(userWithRole('Admin'))->delete(route('departments.destroy', $department))->assertSessionHas('success');

    assertSoftDeleted($department);
});

test('a department with employees can not be deleted', function () {
    $department = Department::factory()->create();
    Employee::factory()->for($department)->create();

    actingAs(userWithRole('Admin'))->delete(route('departments.destroy', $department))->assertSessionHas('error');

    expect($department->fresh()->trashed())->toBeFalse();
});

test('the manager dropdown lists only active managers', function () {
    $active = userWithRole('Manager', ['name' => 'Active Manager']);
    userWithRole('Manager', ['name' => 'Former Manager', 'is_active' => false]);
    userWithRole('Employee', ['name' => 'Plain Employee']);

    actingAs(userWithRole('Admin'))->get(route('departments.create'))
        ->assertSee('value="'.$active->id.'"', false)
        ->assertSeeText('Active Manager')
        ->assertDontSeeText('Former Manager')
        ->assertDontSeeText('Plain Employee');
});

test('a deleted department is gone from edit, the employee form and the filter', function () {
    $deleted = Department::factory()->create(['name' => 'Old Department']);
    $deleted->delete();
    $admin = userWithRole('Admin');

    actingAs($admin)->get(route('departments.edit', $deleted))->assertNotFound();
    actingAs($admin)->get(route('employees.create'))->assertDontSeeText('Old Department');
    actingAs($admin)->get(route('employees.index'))->assertDontSeeText('Old Department');
});

test('users without departments.manage get 403 on every department page', function (string $role) {
    $user = userWithRole($role);
    $department = Department::factory()->create();

    actingAs($user)->get(route('departments.index'))->assertForbidden();
    actingAs($user)->get(route('departments.create'))->assertForbidden();
    actingAs($user)->post(route('departments.store'), ['name' => 'X'])->assertForbidden();
    actingAs($user)->get(route('departments.edit', $department))->assertForbidden();
    actingAs($user)->put(route('departments.update', $department), ['name' => 'X'])->assertForbidden();
    actingAs($user)->delete(route('departments.destroy', $department))->assertForbidden();
})->with(['HR', 'Manager', 'Employee']);

test('after saving, the message is handed to the toast and delete asks for confirmation', function () {
    actingAs(userWithRole('Admin'))
        ->followingRedirects()
        ->post(route('departments.store'), ['name' => 'Finance'])
        ->assertSee('data-flash-success="Department created."', false)
        ->assertSee('data-confirm="Delete department?"', false);
});
