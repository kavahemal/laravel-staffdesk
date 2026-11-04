<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Notification;

/** To the employee when their leave is approved or rejected. */
class LeaveStatusChanged extends Notification
{
    public function __construct(public readonly LeaveRequest $leave) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $leave = $this->leave->loadMissing('leaveType', 'actionedBy');
        $status = strtolower($leave->status->label());

        return [
            'leave_request_id' => $leave->id,
            'status' => $leave->status->value,
            'message' => "Your {$leave->leaveType->name} ({$leave->period()}) was {$status} by {$leave->actionedBy?->name}."
                .($leave->comment ? " Comment: {$leave->comment}" : ''),
            'url' => route('leaves.index', absolute: false),
        ];
    }
}
