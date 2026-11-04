<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Notification;

/** To the employee when HR/Admin records (already approved) leave for them. */
class LeaveRecorded extends Notification
{
    public function __construct(public readonly LeaveRequest $leave) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $leave = $this->leave->loadMissing('leaveType', 'actionedBy');

        return [
            'leave_request_id' => $leave->id,
            'message' => "{$leave->actionedBy?->name} recorded {$leave->leaveType->name} ({$leave->period()}) for you: {$leave->reason}",
            'url' => route('leaves.index', absolute: false),
        ];
    }
}
