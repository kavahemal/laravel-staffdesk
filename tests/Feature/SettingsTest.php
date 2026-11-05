<?php

use App\Models\Setting;
use App\Notifications\TestEmail;
use App\Services\LeaveService;
use App\Services\SettingService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

/** A valid settings form; `$changes` replaces work week days, `$general` the other fields. */
function workWeekInput(array $changes = [], array $general = []): array
{
    return [
        'company_name' => 'HRMS',
        'date_format' => 'd M Y',
        'mail_mailer' => 'log',
        'mail_from_address' => 'hr@example.com',
        ...$general,
        'work_week' => array_replace(
            [1 => 'full', 2 => 'full', 3 => 'full', 4 => 'full', 5 => 'full', 6 => 'full', 0 => 'off'],
            $changes,
        ),
    ];
}

test('admin sees the work week, defaulting to Monday to Saturday', function () {
    actingAs(userWithRole('Admin'))->get(route('settings.edit'))
        ->assertOk()
        ->assertSeeInOrder(['Monday', 'Saturday', 'Sunday'])
        ->assertSee('<option value="off" selected>', false);
});

test('admin saves the work week and leave is counted with it', function () {
    actingAs(userWithRole('Admin'))
        ->put(route('settings.update'), workWeekInput([6 => 'half']))
        ->assertRedirect(route('settings.edit'))
        ->assertSessionHas('success', 'Settings saved.');

    expect(Setting::firstWhere('key', 'work_week')->value[6])->toBe('half')
        ->and(app(SettingService::class)->workWeek()->toArray()[6])->toBe('half')
        // Mon 9 – Sun 15 Nov 2026 with a half Saturday.
        ->and(app(LeaveService::class)->countDays(Carbon::parse('2026-11-09'), Carbon::parse('2026-11-15')))->toBe(5.5);

    actingAs(userWithRole('Admin'))->get(route('settings.edit'))->assertSee('<option value="half" selected>', false);
});

test('company name and date format are used across the app', function () {
    travelTo(Carbon::parse('2026-11-05 10:00'));
    $admin = userWithRole('Admin');

    actingAs($admin)->get(route('settings.edit'))
        ->assertSee('value="HRMS"', false)
        ->assertSee('<option value="d M Y" selected>05 Nov 2026</option>', false);

    actingAs($admin)->put(route('settings.update'), workWeekInput(general: ['company_name' => 'Acme Corp', 'date_format' => 'd/m/Y']))
        ->assertSessionHasNoErrors();

    expect(format_date(Carbon::parse('2026-11-05')))->toBe('05/11/2026');

    actingAs($admin)->get(route('settings.edit'))
        ->assertSee('<title>Settings | Acme Corp</title>', false)
        ->assertSee('<option value="d/m/Y" selected>05/11/2026</option>', false);

    actingAs(userWithRole('HR'))->get(route('leaves.record'))->assertSee('data-display-format="d/m/Y"', false);

    Auth::logout();
    get(route('login'))->assertSeeText('Acme Corp');
});

test('an uploaded logo replaces the default logo and favicon, and can be removed again', function () {
    /** @var FilesystemAdapter $disk */
    $disk = Storage::fake('branding');
    $admin = userWithRole('Admin');

    actingAs($admin)->put(route('settings.update'), workWeekInput(general: ['logo' => UploadedFile::fake()->image('first.png', 64, 64)]));
    $first = app(SettingService::class)->logoPath();

    actingAs($admin)->put(route('settings.update'), workWeekInput(general: ['logo' => UploadedFile::fake()->image('second.png', 64, 64)]))
        ->assertSessionHasNoErrors();
    $logoUrl = app(SettingService::class)->logoUrl();

    $disk->assertMissing($first);
    actingAs($admin)->get(route('dashboard'))
        ->assertSee('<link rel="icon" href="'.$logoUrl.'">', false)
        ->assertSee('<img src="'.$logoUrl.'"', false);
    get($logoUrl)->assertOk()->assertHeader('Content-Type', 'image/png');

    actingAs($admin)->put(route('settings.update'), workWeekInput(general: ['remove_logo' => '1']))
        ->assertSessionHas('success');

    $disk->assertDirectoryEmpty('/');
    get(route('logo'))->assertNotFound();
    actingAs($admin)->get(route('dashboard'))->assertSee('favicon.svg');
});

test('the login page shows the logo to guests', function () {
    Storage::fake('branding');
    app(SettingService::class)->saveLogo(UploadedFile::fake()->image('logo.png', 64, 64));
    $logoUrl = app(SettingService::class)->logoUrl();

    get(route('login'))->assertSee('<img src="'.$logoUrl.'"', false);
    get($logoUrl)->assertOk();
});

test('work week input is validated', function (array $input, string $field) {
    actingAs(userWithRole('Admin'))->put(route('settings.update'), $input)->assertSessionHasErrors($field);

    expect(Setting::count())->toBe(0);
})->with([
    'unknown value' => [workWeekInput([3 => 'sometimes']), 'work_week.3'],
    'missing day' => [['work_week' => [1 => 'full']], 'work_week.2'],
    'every day off' => [workWeekInput(array_fill(0, 7, 'off')), 'work_week'],
    'no company name' => [workWeekInput(general: ['company_name' => '']), 'company_name'],
    'company name too long' => [workWeekInput(general: ['company_name' => str_repeat('a', 51)]), 'company_name'],
    'unknown date format' => [workWeekInput(general: ['date_format' => 'Y/d/m']), 'date_format'],
    'logo not an image' => [workWeekInput(general: ['logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf')]), 'logo'],
    'svg logo' => [workWeekInput(general: ['logo' => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml')]), 'logo'],
    'logo too large' => [workWeekInput(general: ['logo' => UploadedFile::fake()->image('logo.png')->size(2048)]), 'logo'],
    'smtp without host' => [workWeekInput(general: smtpInput(['mail_host' => ''])), 'mail_host'],
    'port out of range' => [workWeekInput(general: smtpInput(['mail_port' => 70000])), 'mail_port'],
    'unknown encryption' => [workWeekInput(general: smtpInput(['mail_scheme' => 'tls'])), 'mail_scheme'],
    'bad from address' => [workWeekInput(general: ['mail_from_address' => 'not-an-email']), 'mail_from_address'],
]);

test('users without settings.manage get 403', function (string $role) {
    $user = userWithRole($role);

    actingAs($user)->get(route('settings.edit'))->assertForbidden();
    actingAs($user)->put(route('settings.update'), workWeekInput())->assertForbidden();
    actingAs($user)->post(route('settings.test-email'))->assertForbidden();
})->with(['HR', 'Manager', 'Employee']);

// Email

/** SMTP settings as the form sends them. */
function smtpInput(array $changes = []): array
{
    return [
        'mail_mailer' => 'smtp',
        'mail_host' => 'smtp.example.com',
        'mail_port' => '587',
        'mail_scheme' => 'smtp',
        'mail_username' => 'hrms',
        'mail_password' => 'secret-pass',
        'mail_from_address' => 'hr@example.com',
        'mail_from_name' => '',
        ...$changes,
    ];
}

test('SMTP settings are saved with an encrypted password that a blank field keeps', function () {
    $admin = userWithRole('Admin');

    actingAs($admin)->put(route('settings.update'), workWeekInput(general: smtpInput()))->assertSessionHasNoErrors();
    actingAs($admin)->put(route('settings.update'), workWeekInput(general: smtpInput(['mail_password' => '', 'mail_port' => '2525'])));

    $stored = Setting::firstWhere('key', 'mail')->value;
    expect($stored['port'])->toBe(2525)
        ->and($stored['password'])->not->toContain('secret-pass')
        ->and(Crypt::decryptString($stored['password']))->toBe('secret-pass');

    actingAs($admin)->get(route('settings.edit'))
        ->assertSee('value="smtp.example.com"', false)
        ->assertSee('Saved – leave blank to keep it')
        ->assertDontSee('secret-pass');

    // No username, no password.
    actingAs($admin)->put(route('settings.update'), workWeekInput(general: smtpInput(['mail_username' => '', 'mail_password' => 'ignored'])));
    expect(Setting::firstWhere('key', 'mail')->value['password'])->toBeNull();
});

test('saved email settings are used when mail is sent; without them .env applies', function () {
    $settings = app(SettingService::class);

    $settings->applyMailConfig();
    expect(config('mail.default'))->toBe('array'); // phpunit.xml

    $settings->saveGeneral('Acme Corp', 'd M Y');
    $settings->saveMail(['mailer' => 'smtp', 'host' => 'smtp.example.com', 'port' => 465, 'scheme' => 'smtps',
        'username' => 'hrms', 'password' => 'secret-pass', 'from_address' => 'hr@example.com', 'from_name' => null]);
    app()->forgetInstance('mail.manager');
    app('mail.manager');

    expect(config('mail.default'))->toBe('smtp')
        ->and(config('mail.mailers.smtp'))->toMatchArray(['host' => 'smtp.example.com', 'port' => 465, 'scheme' => 'smtps', 'password' => 'secret-pass'])
        ->and(config('mail.from'))->toBe(['address' => 'hr@example.com', 'name' => 'Acme Corp']);
});

test('the test email goes to the signed-in admin', function () {
    Notification::fake();
    $admin = userWithRole('Admin');

    actingAs($admin)->from(route('settings.edit'))->post(route('settings.test-email'))
        ->assertRedirect(route('settings.edit'))
        ->assertSessionHas('success', "Test email sent to {$admin->email}.");

    Notification::assertSentTo($admin, TestEmail::class);
});

test('a failing mail server is reported, not thrown', function () {
    $admin = userWithRole('Admin');
    actingAs($admin)->put(route('settings.update'), workWeekInput(general: smtpInput(['mail_host' => '127.0.0.1', 'mail_port' => '1', 'mail_username' => ''])));
    app()->forgetInstance('mail.manager');

    actingAs($admin)->post(route('settings.test-email'))
        ->assertSessionHas('error', fn (string $message) => str_starts_with($message, 'The test email could not be sent:'));
});
