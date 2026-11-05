<?php

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\call;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\travelTo;

// Today is Wednesday 2026-11-04; 2026-11-09 is a Monday.
beforeEach(fn () => travelTo(Carbon::parse('2026-11-04 10:00')));

// Login / logout

test('login returns a bearer token that opens the API', function () {
    $user = userWithRole('Employee', ['email' => 'sam@example.com']);

    $token = postJson(route('api.v1.login'), ['email' => 'sam@example.com', 'password' => 'password', 'device_name' => 'Pixel'])
        ->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('expires_at', now()->addDays(config('hrms.api_token_days'))->toIso8601String())
        ->json('token');

    expect($user->tokens()->sole()->name)->toBe('Pixel');
    getJson(route('api.v1.leaves.index'), ['Authorization' => "Bearer {$token}"])->assertOk();
});

test('wrong password and inactive accounts get the same 422', function (array $attributes, string $password) {
    userWithRole('Employee', ['email' => 'sam@example.com', ...$attributes]);

    postJson(route('api.v1.login'), ['email' => 'sam@example.com', 'password' => $password])
        ->assertUnprocessable()
        ->assertExactJson([
            'message' => 'These credentials do not match our records.',
            'errors' => ['email' => ['These credentials do not match our records.']],
        ]);
})->with([
    'wrong password' => [[], 'wrong'],
    'inactive' => [['is_active' => false], 'password'],
]);

test('login attempts are rate limited', function () {
    foreach (range(1, config('hrms.api_login_rate_limit')) as $attempt) {
        postJson(route('api.v1.login'), ['email' => 'sam@example.com', 'password' => 'wrong'])->assertUnprocessable();
    }

    postJson(route('api.v1.login'), ['email' => 'sam@example.com', 'password' => 'wrong'])->assertTooManyRequests();
});

test('logout revokes only the token that was used', function () {
    $user = userWithRole('Employee');
    $phone = $user->createToken('phone')->plainTextToken;
    $tablet = $user->createToken('tablet')->plainTextToken;

    postJson(route('api.v1.logout'), [], ['Authorization' => "Bearer {$phone}"])->assertNoContent();

    expect($user->tokens()->pluck('name')->all())->toBe(['tablet']);
    app('auth')->forgetGuards();
    getJson(route('api.v1.leaves.index'), ['Authorization' => "Bearer {$phone}"])->assertUnauthorized();
    app('auth')->forgetGuards();
    getJson(route('api.v1.leaves.index'), ['Authorization' => "Bearer {$tablet}"])->assertOk();
});

test('every endpoint except login needs a token, and answers 401 in JSON even without an Accept header', function (string $method, string $route) {
    call($method, route($route, LeaveRequest::factory()->create()))
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
})->with([
    ['POST', 'api.v1.logout'],
    ['GET', 'api.v1.leave-types.index'],
    ['GET', 'api.v1.leaves.index'],
    ['POST', 'api.v1.leaves.store'],
    ['POST', 'api.v1.leaves.cancel'],
]);

test('a deactivated user\'s tokens stop working', function () {
    $employee = Employee::factory()->create();
    $token = $employee->user->createToken('phone')->plainTextToken;

    actingAs(userWithRole('Admin'))->patch(route('employees.status', $employee), ['active' => 0]);
    app('auth')->forgetGuards();

    expect($employee->user->tokens()->count())->toBe(0);
    getJson(route('api.v1.leaves.index'), ['Authorization' => "Bearer {$token}"])->assertUnauthorized();
});

// Leave

test('leave types list the active types with this year\'s balance', function () {
    $user = userWithRole('Employee');
    $casual = LeaveType::factory()->create(['name' => 'Casual', 'yearly_quota' => 10]);
    LeaveType::factory()->inactive()->create(['name' => 'Old']);
    LeaveRequest::factory()->for($user)->for($casual)->on('2026-11-09', days: 2)->create();
    Sanctum::actingAs($user);

    getJson(route('api.v1.leave-types.index'))
        ->assertOk()
        ->assertExactJson(['year' => 2026, 'data' => [
            ['id' => $casual->id, 'name' => 'Casual', 'yearly_quota' => 10, 'used' => 2, 'remaining' => 8],
        ]]);
});

test('the list shows only own leave, latest first, paginated', function () {
    $user = userWithRole('Employee');
    $older = LeaveRequest::factory()->for($user)->on('2026-03-02')->create(['status' => LeaveStatus::Approved]);
    // Created last but starts first.
    $newer = LeaveRequest::factory()->for($user)->on('2026-11-09')->create();
    LeaveRequest::factory()->for(userWithRole('Employee'))->create();
    Sanctum::actingAs($user);

    getJson(route('api.v1.leaves.index'))
        ->assertOk()
        ->assertJsonPath('data.*.id', [$newer->id, $older->id])
        ->assertJsonPath('data.0.can_cancel', true)
        ->assertJsonPath('data.1.can_cancel', false)
        ->assertJsonPath('meta.total', 2);

    getJson(route('api.v1.leaves.index', ['filter' => ['status' => 'approved']]))->assertJsonPath('data.*.id', [$older->id]);
    getJson(route('api.v1.leaves.index', ['filter' => ['reason' => 'x']]))->assertBadRequest()->assertJsonStructure(['message']);
});

test('apply via the API uses the same rules as the web form', function () {
    $user = userWithRole('Employee');
    $type = LeaveType::factory()->create(['yearly_quota' => 10]);
    Sanctum::actingAs($user);

    postJson(route('api.v1.leaves.store'), [
        'leave_type_id' => $type->id, 'from_date' => '2026-11-09', 'to_date' => '2026-11-10', 'reason' => 'Family event',
    ])
        ->assertCreated()
        ->assertJsonPath('data.leave_type.id', $type->id)
        ->assertJsonPath('data.days', 2)
        ->assertJsonPath('data.status', 'pending');

    expect($user->leaveRequests()->sole()->reason)->toBe('Family event');
});

test('validation and leave-rule errors come back as 422 JSON', function (array $data, string $field, string $message) {
    $type = LeaveType::factory()->create(['yearly_quota' => 10]);
    Sanctum::actingAs(userWithRole('Employee'));

    postJson(route('api.v1.leaves.store'), [
        'leave_type_id' => $type->id, 'from_date' => '2026-11-09', 'to_date' => '2026-11-10', 'reason' => 'Trip', ...$data,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', $message)
        ->assertJsonPath("errors.{$field}.0", $message);
})->with([
    'missing reason' => [['reason' => ''], 'reason', 'The reason field is required.'],
    'past date' => [['from_date' => '2026-11-02'], 'from_date', 'The start date can not be in the past.'],
]);

test('users without leaves.apply can not use the leave endpoints', function () {
    Sanctum::actingAs(userWithRole('Admin'));

    getJson(route('api.v1.leaves.index'))->assertForbidden()->assertJsonStructure(['message']);
    postJson(route('api.v1.leaves.store'), [])->assertForbidden();
});

test('cancel: own leave that has not started only', function () {
    $user = userWithRole('Employee');
    $pending = LeaveRequest::factory()->for($user)->on('2026-11-09')->create();
    $approved = LeaveRequest::factory()->for($user)->on('2026-11-02', '2026-11-05')->create(['status' => LeaveStatus::Approved]);
    $others = LeaveRequest::factory()->for(userWithRole('Employee'))->on('2026-11-09')->create();
    Sanctum::actingAs($user);

    postJson(route('api.v1.leaves.cancel', $pending))->assertOk()->assertJsonPath('data.status', 'cancelled')->assertJsonPath('data.can_cancel', false);
    postJson(route('api.v1.leaves.cancel', $approved))->assertForbidden();
    postJson(route('api.v1.leaves.cancel', $others))->assertForbidden();
    postJson(route('api.v1.leaves.cancel', 999))->assertNotFound()->assertExactJson(['message' => 'Not found.']);

    expect($others->fresh()->status)->toBe(LeaveStatus::Pending);
});
