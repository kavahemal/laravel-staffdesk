<?php

use App\Models\Employee;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('forms skip browser validation (server-side only)', function () {
    get('/login')->assertSee('novalidate', false);

    foreach (['profile.edit', 'departments.create', 'employees.create'] as $route) {
        actingAs(userWithRole('Admin'))->get(route($route))->assertSee('novalidate', false);
    }
});

test('date fields use the date picker and keep a Y-m-d value', function () {
    $employee = Employee::factory()->create(['joining_date' => '2025-01-15']);

    actingAs(userWithRole('Admin'))->get(route('employees.edit', $employee))
        ->assertSee('data-datepicker="single"', false)
        ->assertSee('data-display-format="d M Y"', false)
        ->assertSee('value="2025-01-15"', false)
        ->assertDontSee('type="date"', false);
});

test('a field with a server error is shown red with its message', function () {
    actingAs(userWithRole('Admin'))
        ->from(route('departments.create'))
        ->followingRedirects()
        ->post(route('departments.store'), ['name' => ''])
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('border-error-500', false)
        ->assertSeeText('The name field is required.');
});

test('errors in a named bag mark the right field', function () {
    $user = Employee::factory()->create()->user;

    $html = actingAs($user)
        ->from(route('profile.edit'))
        ->followingRedirects()
        ->put(route('password.update'), ['current_password' => 'wrong', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
        ->getContent();

    $input = fn (string $id) => preg_match('/<input[^>]*id="'.$id.'"[^>]*>/', $html, $m) ? $m[0] : '';

    expect($input('update_password_current_password'))->toContain('aria-invalid="true"')
        ->and($input('update_password_password'))->not->toContain('aria-invalid')
        ->and($input('name'))->not->toContain('aria-invalid');
});
