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

    /** Matches employee code, designation, name or email; `%` and `_` are taken literally. */
    public function scopeSearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query
            ->where('employee_code', 'like', $like)
            ->orWhere('designation', 'like', $like)
            ->orWhereHas('user', fn (Builder $user) => $user
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)));
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
