<?php

namespace App\Http\Resources;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeaveRequest */
class LeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'leave_type' => ['id' => $this->leaveType->id, 'name' => $this->leaveType->name],
            'from_date' => $this->from_date->toDateString(),
            'to_date' => $this->to_date->toDateString(),
            'half_day' => $this->half_day?->value,
            'days' => $this->days,
            'reason' => $this->reason,
            'status' => $this->status->value,
            'comment' => $this->comment,
            'actioned_at' => $this->actioned_at?->toIso8601String(),
            'applied_at' => $this->created_at->toIso8601String(),
            'can_cancel' => $request->user()->can('cancel', $this->resource),
        ];
    }
}
