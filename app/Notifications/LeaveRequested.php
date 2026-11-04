<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Notification;

/** To everyone who may approve the request: the department manager and approve-any users (HR, Admin). */
class LeaveRequested extends Notification
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
            'message' => "{$leave->user->name} applied for {$leave->leaveType->name}: {$leave->period()} (".format_days($leave->days).' day(s)).',
            'url' => route('approvals.index', absolute: false),
        ];
    }
}
