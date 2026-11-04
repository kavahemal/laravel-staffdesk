<?php

namespace App\Http\Controllers;

use App\Enums\WorkDay;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\SettingService;
use App\Support\WorkWeek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(SettingService $settings): View
    {
        Gate::authorize('settings.manage');

        return view('settings.edit', [
            'workWeek' => $settings->workWeek(),
            'dayNames' => WorkWeek::dayNames(),
            'options' => WorkDay::cases(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, SettingService $settings): RedirectResponse
    {
        $settings->saveWorkWeek(WorkWeek::fromArray($request->validated('work_week')));

        return to_route('settings.edit')->with('success', 'Settings saved.');
    }
}
