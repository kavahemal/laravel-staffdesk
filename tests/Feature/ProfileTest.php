<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    actingAs($user)->get('/profile')->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com');
});

test('account cannot be deleted from the profile', function () {
    $user = User::factory()->create();

    actingAs($user)->delete('/profile')->assertMethodNotAllowed();

    expect($user->fresh())->not->toBeNull();
});
