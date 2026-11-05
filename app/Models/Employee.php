<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'employee_code',
        'designation',
        'joining_date',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
        ];
    }

    /** Employees whose login is active. */
    public function scopeActive(Builder $query): void
    {
        $query->whereHas('user', fn (Builder $user) => $user->active());
    }

    /** The manager's team: people in departments they manage, without the manager. */
    public function scopeTeamOf(Builder $query, User $manager): void
    {
        $query->whereNot('user_id', $manager->id)
            ->whereHas('department', fn (Builder $department) => $department->where('manager_id', $manager->id));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
