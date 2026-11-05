<?php

namespace App\Models;

use Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    /** @use HasFactory<LeaveTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'yearly_quota',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'yearly_quota' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /** Id + name by name, for filter dropdowns (inactive types included – old leave still uses them). */
    public function scopeForSelect(Builder $query): void
    {
        $query->orderBy('name')->select(['id', 'name']);
    }
}
