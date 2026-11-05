<?php

namespace App\Models;

use App\Enums\ExportStatus;
use Database\Factories\ReportExportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A leave report CSV requested by a user, written by a queued job. */
class ReportExport extends Model
{
    /** @use HasFactory<ReportExportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filters',
        'status',
        'path',
        'rows',
        'completed_at',
    ];

    protected $attributes = [
        'status' => ExportStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'status' => ExportStatus::class,
            'rows' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRunning(Builder $query): void
    {
        $query->whereIn('status', ExportStatus::running());
    }
}
