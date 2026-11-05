<?php

namespace App\Http\Controllers;

use App\Enums\WorkDay;
use App\Http\Requests\UpdateSettingsRequest;
use App\Notifications\TestEmail;
use App\Services\SettingService;
use App\Support\WorkWeek;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SettingController extends Controller
{
    public function edit(SettingService $settings): View
    {
        Gate::authorize('settings.manage');

        return view('settings.edit', [
            'companyName' => $settings->companyName(),
            'dateFormat' => $settings->dateFormat(),
            'dateFormats' => config('hrms.date_formats'),
            'hasLogo' => $settings->logoPath() !== null,
            'mail' => $settings->mail(),
            'workWeek' => $settings->workWeek(),
            'dayNames' => WorkWeek::dayNames(),
            'options' => WorkDay::cases(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, SettingService $settings): RedirectResponse
    {
        $settings->saveGeneral($request->validated('company_name'), $request->validated('date_format'));

        if ($request->hasFile('logo')) {
            $settings->saveLogo($request->file('logo'));
        } elseif ($request->boolean('remove_logo')) {
            $settings->removeLogo();
        }

        $settings->saveWorkWeek(WorkWeek::fromArray($request->validated('work_week')));
        $settings->saveMail($request->mailSettings());

        return to_route('settings.edit')->with('success', 'Settings saved.');
    }

    /** Sends a test email to the signed-in admin with the saved email settings; a server error is shown as is. */
    public function testEmail(Request $request): RedirectResponse
    {
        Gate::authorize('settings.manage');

        try {
            $request->user()->notifyNow(new TestEmail);
        } catch (TransportExceptionInterface $e) {
            return back()->with('error', 'The test email could not be sent: '.$e->getMessage());
        }

        return back()->with('success', config('mail.default') === 'log'
            ? 'Emails are off – the test email was written to the log file.'
            : "Test email sent to {$request->user()->email}.");
    }

    /** The uploaded logo (also the favicon); public, since the login page shows it too. */
    public function logo(SettingService $settings): StreamedResponse
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('branding');
        $path = $settings->logoPath();

        abort_unless($path && $disk->exists($path), 404);

        // The URL changes with every upload, so the browser may keep the image for good.
        return $disk->response($path, headers: ['Cache-Control' => 'public, max-age=31536000, immutable']);
    }
}
