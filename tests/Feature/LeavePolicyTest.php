<?php

use App\Enums\LeaveStatus;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;

test('who may approve whose leave', function (string $approver, string $applicant, bool $allowed) {
    $people = approvalPeople();
    $leave = LeaveRequest::factory()->for($people[$applicant])->create();

    expect($people[$approver]->can('approve', $leave))->toBe($allowed);
})->with([
    'HR – any department' => ['hr', 'sales employee', true],
    'admin – any department' => ['admin', 'support employee', true],
    'manager – own department' => ['manager', 'sales employee', true],
    'manager – other department' => ['manager', 'support employee', false],
    'manager – HR from another department' => ['manager', 'hr', false],
    'employee – a colleague' => ['sales employee', 'manager', false],
    'HR – own leave' => ['hr', 'hr', false],
    'manager – own leave in own department' => ['manager', 'manager', false],
]);

test('only pending leave can be approved', function (LeaveStatus $status) {
    $people = approvalPeople();
    $leave = LeaveRequest::factory()->for($people['sales employee'])->create(['status' => $status]);

    expect($people['hr']->can('approve', $leave))->toBeFalse()
        ->and($people['manager']->can('approve', $leave))->toBeFalse();
})->with([LeaveStatus::Approved, LeaveStatus::Rejected, LeaveStatus::Cancelled]);

test('a manager can not approve leave of someone without a department profile', function () {
    $people = approvalPeople();
    $leave = LeaveRequest::factory()->for(User::factory()->create())->create();

    expect($people['manager']->can('approve', $leave))->toBeFalse();
});

test('a manager of a deleted department no longer approves its leave', function () {
    $people = approvalPeople();
    $leave = LeaveRequest::factory()->for($people['sales employee'])->create();

    Department::whereManagerId($people['manager']->id)->sole()->delete();

    expect($people['manager']->can('approve', $leave))->toBeFalse();
});
