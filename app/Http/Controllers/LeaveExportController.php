<?php

namespace App\Http\Controllers;

use App\Enums\ExportStatus;
use App\Http\Requests\LeaveExportRequest;
use App\Models\ReportExport;
use App\Services\ReportExportService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveExportController extends Controller
{
    public function store(LeaveExportRequest $request, ReportExportService $exports): RedirectResponse
    {
        return $exports->startLeaveExport($request->user(), $request->filters())
            ? back()->with('success', 'Export started. You will get a notification when the file is ready.')
            : back()->with('error', 'Your previous export is still running.');
    }

    /** Reached through the signed link in the ExportReady notification. */
    public function download(ReportExport $export): StreamedResponse
    {
        Gate::authorize('download', $export);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('exports');
        abort_unless($export->status === ExportStatus::Done && $disk->exists($export->path), 404);

        return $disk->download($export->path, 'leave-report-'.$export->completed_at->format('Y-m-d-His').'.csv');
    }
}
