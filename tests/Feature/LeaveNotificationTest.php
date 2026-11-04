<?php

use App\Enums\LeaveStatus;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveRequested;
use App\Notifications\LeaveStatusChanged;
use App\Services\LeaveService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(fn () => travelTo(Carbon::parse('2026-11-04 10:00')));

function applyFor(User $user): LeaveRequest
{
    return app(LeaveService::class)->apply($user, [
        'leave_type_id' => LeaveType::firstOrCreate(['name' => 'Casual Leave'], ['yearly_quota' => 10])->id,
        'from_date' => '2026-11-09',
        'to_date' => '2026-11-10',
        'reason' => 'Family event',
    ]);
}

// Sending

test('a new request notifies the department manager, HR and admin – nobody else', function () {
    Notification::fake();
    $people = approvalPeople();
    $inactiveHr = userWithRole('HR', ['is_active' => false]);
    $supportManager = Department::with('manager')->firstWhere('name', 'Support')->manager;

    applyFor($people['sales employee']);

    Notification::assertSentTo([$people['manager'], $people['hr'], $people['admin']], LeaveRequested::class);
    Notification::assertNotSentTo([$people['sales employee'], $people['support employee'], $supportManager, $inactiveHr], LeaveRequested::class);
});

test('a manager applying is not notified about their own request', function () {
    Notification::fake();
    $people = approvalPeople();

    applyFor($people['manager']);

    Notification::assertNotSentTo($people['manager'], LeaveRequested::class);
    Notification::assertSentTo([$people['hr'], $people['admin']], LeaveRequested::class);
});

test('the employee is notified when their leave is approved or rejected', function (LeaveStatus $decision, string $word) {
    $people = approvalPeople();
    $leave = applyFor($people['sales employee']);

    app(LeaveService::class)->decide($leave, $people['manager'], $decision, 'See you soon');

    $notification = $people['sales employee']->notifications()->sole();

    expect($notification->type)->toBe(LeaveStatusChanged::class)
        ->and($notification->data['message'])
        ->toBe("Your Casual Leave (09 Nov 2026 – 10 Nov 2026) was {$word} by {$people['manager']->name}. Comment: See you soon")
        ->and($notification->data['url'])->toBe('/leaves');
})->with([
    [LeaveStatus::Approved, 'approved'],
    [LeaveStatus::Rejected, 'rejected'],
]);

test('nothing is sent when the leave was no longer pending', function () {
    Notification::fake();
    $leave = LeaveRequest::factory()->cancelled()->create();

    app(LeaveService::class)->decide($leave, userWithRole('HR'), LeaveStatus::Approved, null);

    Notification::assertNothingSent();
});

// Header bell

test('the bell shows the unread count and the latest messages', function () {
    $people = approvalPeople();
    applyFor($people['sales employee']);

    actingAs($people['manager'])->get(route('dashboard'))
        ->assertSeeText('Mark all as read')
        ->assertSeeText("{$people['sales employee']->name} applied for Casual Leave: 09 Nov 2026 – 10 Nov 2026 (2 day(s)).")
        ->assertSee('>1</span>', false);

    actingAs($people['support employee'])->get(route('dashboard'))->assertSeeText('No notifications yet.');
});

test('opening a notification marks it read and goes to its page', function () {
    $people = approvalPeople();
    applyFor($people['sales employee']);
    $notification = $people['manager']->notifications()->sole();

    actingAs($people['manager'])->post(route('notifications.read', $notification->id))
        ->assertRedirect('/approvals');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('mark all as read', function () {
    $people = approvalPeople();
    applyFor($people['sales employee']);
    applyFor($people['support employee']);

    actingAs($people['hr'])->post(route('notifications.read-all'))->assertRedirect();

    expect($people['hr']->unreadNotifications()->count())->toBe(0);
});

test('nobody can open another user\'s notification', function () {
    $people = approvalPeople();
    applyFor($people['sales employee']);
    $notification = $people['manager']->notifications()->sole();

    actingAs($people['hr'])->post(route('notifications.read', $notification->id))->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});
