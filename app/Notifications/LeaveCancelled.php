<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Notification;

/** To the approvers (department manager, HR, Admin) when an employee cancels approved leave. */
class LeaveCancelled extends Notification
{
    public function __construct(public readonly LeaveRequest $leave) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $leave = $this->leave->loadMissing('user', 'leaveType');

        return [
            'leave_request_id' => $leave->id,
            'message' => "{$leave->user->name} cancelled their approved {$leave->leaveType->name}: {$leave->period()} (".format_days($leave->days).' day(s)).',
            'url' => route('approvals.index', ['filter' => ['status' => 'cancelled']], absolute: false),
        ];
    }
}
