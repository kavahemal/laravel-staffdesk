<?php

namespace App\Policies;

use App\Models\ReportExport;
use App\Models\User;

class ReportExportPolicy
{
    /** Export the leave report as CSV – the same people who see the report. */
    public function create(User $user): bool
    {
        return $user->can('reports.view');
    }

    /** Only the user who asked for the file, and only while they still see the report. */
    public function download(User $user, ReportExport $export): bool
    {
        return $export->user_id === $user->id && $user->can('reports.view');
    }
}
