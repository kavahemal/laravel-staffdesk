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

    /** Matches the name; `%` and `_` are taken literally. */
    public function scopeSearch(Builder $query, string $term): void
    {
        $query->where('name', 'like', like_pattern($term));
    }
}
