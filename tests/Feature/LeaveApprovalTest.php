<?php

use App\Enums\LeaveStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(fn () => travelTo(Carbon::parse('2026-11-04 10:00')));

/** Leave with a unique reason to find its row (names also appear in the filters). */
function pendingLeave(User $user, string $reason, string $from = '2026-11-09', LeaveStatus $status = LeaveStatus::Pending): LeaveRequest
{
    return LeaveRequest::factory()->for($user)->on($from)->create(['reason' => $reason, 'status' => $status]);
}

// List

test('HR sees pending leave of every department by default, never their own', function () {
    $people = approvalPeople();
    pendingLeave($people['sales employee'], 'Sales trip');
    pendingLeave($people['support employee'], 'Support trip');
    pendingLeave($people['hr'], 'HR own trip');
    pendingLeave($people['sales employee'], 'Sales approved trip', status: LeaveStatus::Approved);

    actingAs($people['hr'])->get(route('approvals.index'))
        ->assertOk()
        ->assertSeeText(['Sales trip', 'Support trip', 'All departments'])
        ->assertDontSeeText('HR own trip')
        ->assertDontSeeText('Sales approved trip');
});

test('a manager sees only pending leave of their own team', function () {
    $people = approvalPeople();
    pendingLeave($people['sales employee'], 'Sales trip');
    pendingLeave($people['support employee'], 'Support trip');
    pendingLeave($people['manager'], 'Manager own trip');

    actingAs($people['manager'])->get(route('approvals.index'))
        ->assertOk()
        ->assertSeeText('Sales trip')
        ->assertDontSeeText('Support trip')
        ->assertDontSeeText('Manager own trip')
        ->assertDontSeeText('All departments');
});

test('decided leave shows with the status filter, with who decided it', function () {
    $people = approvalPeople();
    pendingLeave($people['sales employee'], 'Sales pending trip');
    pendingLeave($people['manager'], 'Manager own trip', status: LeaveStatus::Approved);
    LeaveRequest::factory()->for($people['sales employee'])->on('2026-11-16')->approved($people['manager'])
        ->create(['reason' => 'Sales approved trip', 'comment' => 'Enjoy']);
    $manager = $people['manager'];

    actingAs($manager)->get(route('approvals.index', ['filter' => ['status' => 'all']]))
        ->assertSeeText(['Sales pending trip', 'Sales approved trip', "by {$manager->name}", 'Enjoy'])
        ->assertDontSeeText('Manager own trip');

    actingAs($manager)->get(route('approvals.index', ['filter' => ['status' => 'approved']]))
        ->assertSeeText('Sales approved trip')
        ->assertDontSeeText('Sales pending trip')
        ->assertDontSee('data-confirm="Approve leave?"', false);
});

test('the list starts with the earliest leave and can be filtered and sorted', function () {
    $people = approvalPeople();
    pendingLeave($people['sales employee'], 'Later trip', '2026-11-20');
    // Created last but starts first.
    pendingLeave($people['support employee'], 'Early trip', '2026-11-05');
    $hr = $people['hr'];

    actingAs($hr)->get(route('approvals.index'))->assertSeeInOrder(['Early trip', 'Later trip']);
    actingAs($hr)->get(route('approvals.index', ['sort' => '-employee']))->assertSeeInOrder(['Later trip', 'Early trip']);
    actingAs($hr)->get(route('approvals.index', ['filter' => ['department' => Department::firstWhere('name', 'Sales')->id]]))
        ->assertSeeText('Later trip')->assertDontSeeText('Early trip');
    actingAs($hr)->get(route('approvals.index', ['sort' => 'reason']))->assertBadRequest();
});

test('pending rows show the balance left and who in the department is already off', function () {
    $people = approvalPeople();
    $casual = LeaveType::factory()->create(['name' => 'Casual Leave', 'yearly_quota' => 10]);
    bookedLeave($people['sales employee'], $casual, '2026-11-09', '2026-11-10', 2, LeaveStatus::Pending);
    bookedLeave($people['sales employee'], $casual, '2026-03-02', null, 3);
    bookedLeave($people['sales employee'], $casual, '2025-03-02', null, 4);           // last year
    bookedLeave($people['manager'], $casual, '2026-11-10', null, 1);                  // same department, overlaps
    bookedLeave(userWithRole('Employee'), $casual, '2026-11-10', null, 1);            // no department
    bookedLeave($people['support employee'], $casual, '2026-11-10', null, 1);         // other department
    bookedLeave($people['support employee'], $casual, '2026-11-20', null, 1, LeaveStatus::Pending);
    Employee::factory()->for($sam2 = userWithRole('Employee', ['name' => 'Sam Rejected']))
        ->for(Department::firstWhere('name', 'Sales'))->create();
    bookedLeave($sam2, $casual, '2026-11-09', null, 1, LeaveStatus::Rejected);        // not off

    actingAs($people['hr'])->get(route('approvals.index'))
        ->assertOk()
        ->assertSeeInOrder(['Sam Sales', '2', '5 left', 'Alex Support', '8 left'])
        ->assertSee('title="Already off: '.$people['manager']->name.'"', false)
        ->assertSeeText($people['manager']->name.' is off too')
        ->assertDontSee('Sam Rejected is off too');
});

test('the list shows an empty state', function () {
    actingAs(approvalPeople()['manager'])->get(route('approvals.index'))->assertSeeText('Nothing to approve right now.');
});

test('employees can not open the approvals page', function () {
    actingAs(userWithRole('Employee'))->get(route('approvals.index'))->assertForbidden();
});

// Approve / reject

test('a manager approves team leave with a comment', function () {
    $people = approvalPeople();
    $leave = pendingLeave($people['sales employee'], 'Sales trip');

    actingAs($people['manager'])->get(route('approvals.index'))
        ->assertSee('data-confirm="Approve leave?"', false)
        ->assertSee('data-confirm-comment', false);

    actingAs($people['manager'])
        ->patch(route('leaves.approve', $leave), ['comment' => 'Enjoy the trip'])
        ->assertSessionHas('success', 'Leave approved.');

    expect($leave->fresh())
        ->status->toBe(LeaveStatus::Approved)
        ->actioned_by->toBe($people['manager']->id)
        ->actioned_at->toDateTimeString()->toBe('2026-11-04 10:00:00')
        ->comment->toBe('Enjoy the trip');
});

test('HR rejects leave; an empty comment is stored as null', function () {
    $people = approvalPeople();
    $leave = pendingLeave($people['support employee'], 'Support trip');

    actingAs($people['hr'])->patch(route('leaves.reject', $leave), ['comment' => ''])
        ->assertSessionHas('success', 'Leave rejected.');

    expect($leave->fresh())
        ->status->toBe(LeaveStatus::Rejected)
        ->actioned_by->toBe($people['hr']->id)
        ->comment->toBeNull();
});

// Who may approve whom is covered in LeavePolicyTest; this checks the routes use it.
test('approve and reject are refused when the policy says no', function () {
    $people = approvalPeople();
    $leave = pendingLeave($people['support employee'], 'Trip');

    actingAs($people['manager'])->patch(route('leaves.approve', $leave))->assertForbidden();
    actingAs($people['manager'])->patch(route('leaves.reject', $leave))->assertForbidden();

    expect($leave->fresh()->status)->toBe(LeaveStatus::Pending);
});

test('the comment is limited to 500 characters', function () {
    $people = approvalPeople();
    $leave = pendingLeave($people['sales employee'], 'Sales trip');

    actingAs($people['hr'])->patch(route('leaves.approve', $leave), ['comment' => str_repeat('a', 501)])
        ->assertSessionHasErrors('comment');

    expect($leave->fresh()->status)->toBe(LeaveStatus::Pending);
});

test('a decision only applies to leave that is still pending', function () {
    $hr = userWithRole('HR');
    $cancelled = LeaveRequest::factory()->cancelled()->create();

    // e.g. the employee cancelled while HR had the list open
    expect(app(LeaveService::class)->decide($cancelled, $hr, LeaveStatus::Approved, null))->toBeFalse()
        ->and($cancelled->fresh()->status)->toBe(LeaveStatus::Cancelled)
        ->and(fn () => app(LeaveService::class)->decide(LeaveRequest::factory()->create(), $hr, LeaveStatus::Cancelled, null))
        ->toThrow(InvalidArgumentException::class);
});
