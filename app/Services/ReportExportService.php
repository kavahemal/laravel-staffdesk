<?php

namespace App\Services;

use App\Enums\ExportStatus;
use App\Http\Queries\LeaveReportQuery;
use App\Jobs\GenerateLeaveExport;
use App\Models\LeaveRequest;
use App\Models\ReportExport;
use App\Models\User;
use App\Notifications\ExportReady;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportExportService
{
    private const HEADINGS = [
        'Employee code', 'Employee', 'Department', 'Leave type', 'From', 'To', 'Half day', 'Days',
        'Status', 'Reason', 'Applied on', 'Actioned by', 'Actioned on', 'Comment',
    ];

    /** Queue a leave report CSV; null while the user still has one running (no queue flooding). */
    public function startLeaveExport(User $user, array $filters): ?ReportExport
    {
        if (ReportExport::running()->whereBelongsTo($user)->exists()) {
            return null;
        }

        $export = ReportExport::create(['user_id' => $user->id, 'filters' => $filters]);

        GenerateLeaveExport::dispatch($export);

        return $export;
    }

    /** Write the CSV to the exports disk, mark the export done and notify its owner. */
    public function generateLeaveExport(ReportExport $export): void
    {
        $export->update(['status' => ExportStatus::Processing]);

        $stream = fopen('php://temp', 'w+');
        // UTF-8 byte order mark: Excel otherwise reads the file as ANSI and breaks accented names.
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, self::HEADINGS, escape: '');
        $rows = 0;

        // lazy() reads in chunks, so a large report never sits in memory as one collection.
        foreach (LeaveReportQuery::for(new Request(['filter' => $export->filters]))->lazy() as $leave) {
            fputcsv($stream, $this->row($leave), escape: '');
            $rows++;
        }

        $path = "leave-report-{$export->id}.csv";
        rewind($stream);
        Storage::disk('exports')->writeStream($path, $stream);
        fclose($stream);

        $export->update(['status' => ExportStatus::Done, 'path' => $path, 'rows' => $rows, 'completed_at' => now()]);

        $export->user->notify(new ExportReady($export));
    }

    public function markFailed(ReportExport $export): void
    {
        $export->update(['status' => ExportStatus::Failed]);
    }

    private function row(LeaveRequest $leave): array
    {
        $employee = $leave->user->employee;

        return [
            $this->text($employee?->employee_code),
            $this->text($leave->user->name),
            $this->text($employee?->department?->name),
            $this->text($leave->leaveType->name),
            format_date($leave->from_date),
            format_date($leave->to_date),
            $leave->half_day?->label() ?? '',
            format_days($leave->days),
            $leave->status->label(),
            $this->text($leave->reason),
            format_date($leave->created_at),
            $this->text($leave->actionedBy?->name),
            $leave->actioned_at ? format_date($leave->actioned_at) : '',
            $this->text($leave->comment),
        ];
    }

    /** Typed-in text: a leading = + - @ would run as a formula in Excel, so it is quoted (CSV injection). */
    private function text(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
