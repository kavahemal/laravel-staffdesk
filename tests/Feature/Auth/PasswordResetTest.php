<?php

use App\Models\User;
use App\Services\SettingService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('reset password link screen can be rendered', function () {
    get('/forgot-password')->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('a mail server that is down gives a friendly error, not a 500', function () {
    $user = User::factory()->create();
    app(SettingService::class)->saveMail(['mailer' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'scheme' => 'smtp',
        'username' => null, 'password' => null, 'from_address' => 'hr@example.com', 'from_name' => null]);

    post('/forgot-password', ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHasErrors(['email' => 'The email could not be sent right now. Please try again later or ask HR.']);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        get('/reset-password/'.$notification->token)->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});
