<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Services\ReportExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Writes the leave report CSV for a ReportExport in the background. */
class GenerateLeaveExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public ReportExport $export) {}

    public function handle(ReportExportService $exports): void
    {
        $exports->generateLeaveExport($this->export);
    }

    /** Frees the user to start a new export. */
    public function failed(): void
    {
        app(ReportExportService::class)->markFailed($this->export);
    }
}
