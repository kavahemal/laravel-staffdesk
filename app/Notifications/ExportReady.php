<?php

namespace App\Notifications;

use App\Models\ReportExport;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/** To the user who asked for a leave report CSV, with a signed link that expires. */
class ExportReady extends Notification
{
    public function __construct(public readonly ReportExport $export) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $hours = intdiv(config('staffdesk.export_link_minutes'), 60);

        return [
            'report_export_id' => $this->export->id,
            'message' => "Your leave report CSV is ready ({$this->export->rows} rows). The download link works for {$hours} hours.",
            // Relative, because the bell only redirects to own paths; checked with `signed:relative`.
            'url' => URL::temporarySignedRoute(
                'reports.exports.download',
                now()->addMinutes(config('staffdesk.export_link_minutes')),
                $this->export,
                absolute: false,
            ),
        ];
    }
}
