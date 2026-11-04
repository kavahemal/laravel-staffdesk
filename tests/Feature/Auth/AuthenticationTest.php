<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('the home page redirects to login, which can be rendered', function () {
    get('/')->assertRedirect('/login');
    get('/login')->assertOk();
});

test('login form is prefilled from env only in the local environment', function () {
    config(['hrms.dev_login' => ['email' => 'dev@example.com', 'password' => 'dev-secret']]);

    get('/login')->assertDontSee('dev@example.com')->assertDontSee('dev-secret');

    app()->detectEnvironment(fn () => 'local');

    get('/login')->assertSee('value="dev@example.com"', false)->assertSee('value="dev-secret"', false);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    assertGuest();
});

test('inactive users can not authenticate', function () {
    $user = User::factory()->inactive()->create();

    post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    assertGuest();
});

test('a user deactivated while signed in is logged out', function () {
    $user = User::factory()->create();
    actingAs($user)->get('/dashboard')->assertOk();

    $user->forceFill(['is_active' => false])->save();

    get('/dashboard')
        ->assertRedirect(route('login', absolute: false))
        ->assertSessionHasErrors('email');
    assertGuest();
});

test('registration is not available', function () {
    get('/register')->assertNotFound();
    post('/register')->assertNotFound();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->post('/logout');

    assertGuest();
    $response->assertRedirect('/');
});
