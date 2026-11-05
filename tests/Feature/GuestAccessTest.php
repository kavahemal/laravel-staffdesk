<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\ReportExport;
use Illuminate\Support\Str;

use function Pest\Laravel\call;

test('guests are sent to the login page from every app page', function (string $method, string $route, ?string $model) {
    $parameter = match ($model) {
        'department' => Department::factory()->create(),
        'employee' => Employee::factory()->create(),
        'leave_type' => LeaveType::factory()->create(),
        'leave' => LeaveRequest::factory()->create(),
        'export' => ReportExport::factory()->create(),
        'notification' => (string) Str::uuid(),
        default => null,
    };

    call($method, route($route, $parameter))->assertRedirect(route('login'));
})->with([
    ['GET', 'dashboard', null],
    ['GET', 'profile.edit', null],
    ['GET', 'departments.index', null],
    ['GET', 'departments.create', null],
    ['POST', 'departments.store', null],
    ['GET', 'departments.edit', 'department'],
    ['PUT', 'departments.update', 'department'],
    ['DELETE', 'departments.destroy', 'department'],
    ['GET', 'employees.index', null],
    ['GET', 'employees.create', null],
    ['POST', 'employees.store', null],
    ['GET', 'employees.edit', 'employee'],
    ['PUT', 'employees.update', 'employee'],
    ['PATCH', 'employees.status', 'employee'],
    ['GET', 'leave-types.index', null],
    ['GET', 'leave-types.create', null],
    ['POST', 'leave-types.store', null],
    ['GET', 'leave-types.edit', 'leave_type'],
    ['PUT', 'leave-types.update', 'leave_type'],
    ['DELETE', 'leave-types.destroy', 'leave_type'],
    ['GET', 'leaves.index', null],
    ['GET', 'leaves.balance', null],
    ['GET', 'leaves.record', null],
    ['POST', 'leaves.record.store', null],
    ['GET', 'leaves.create', null],
    ['POST', 'leaves.store', null],
    ['PATCH', 'leaves.cancel', 'leave'],
    ['GET', 'approvals.index', null],
    ['PATCH', 'leaves.approve', 'leave'],
    ['PATCH', 'leaves.reject', 'leave'],
    ['GET', 'reports.leaves', null],
    ['POST', 'reports.leaves.export', null],
    ['GET', 'reports.exports.download', 'export'],
    ['GET', 'notifications.bell', null],
    ['POST', 'notifications.read', 'notification'],
    ['POST', 'notifications.read-all', null],
    ['GET', 'settings.edit', null],
    ['PUT', 'settings.update', null],
]);
