<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\WorkWeek;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/** Settings Admin edits on the Settings page; each falls back to config until saved. Bound scoped: values are read once per request / job. */
class SettingService
{
    private const WORK_WEEK = 'work_week';

    private const COMPANY_NAME = 'company_name';

    private const DATE_FORMAT = 'date_format';

    private const LOGO = 'logo';

    private const MAIL = 'mail';

    /** @var array<string, mixed> values already read in this request */
    private array $loaded = [];

    /** The saved work week, or the default from `config/staffdesk.php`. */
    public function workWeek(): WorkWeek
    {
        return WorkWeek::fromArray($this->get(self::WORK_WEEK) ?? config('staffdesk.work_week'));
    }

    /** Shown in the sidebar, header, login page and browser tab; defaults to APP_NAME. */
    public function companyName(): string
    {
        return $this->get(self::COMPANY_NAME) ?? config('app.name');
    }

    /** PHP date format for every date shown in the app and in exports. */
    public function dateFormat(): string
    {
        $format = $this->get(self::DATE_FORMAT);

        return in_array($format, config('staffdesk.date_formats'), true) ? $format : config('staffdesk.date_format');
    }

    /** File name on the `branding` disk; null = the default logo and favicon. */
    public function logoPath(): ?string
    {
        return $this->get(self::LOGO);
    }

    /** The file name changes on every upload, so browsers may cache the image for good. */
    public function logoUrl(): ?string
    {
        return ($path = $this->logoPath()) ? route('logo', ['v' => pathinfo($path, PATHINFO_FILENAME)]) : null;
    }

    public function saveLogo(UploadedFile $file): void
    {
        $old = $this->logoPath();

        $this->set(self::LOGO, $file->store('', 'branding'));
        $this->deleteLogoFile($old);
    }

    public function removeLogo(): void
    {
        $old = $this->logoPath();

        $this->set(self::LOGO, null);
        $this->deleteLogoFile($old);
    }

    /**
     * Email settings for the form: saved values, else the .env ones. The password itself is never returned.
     *
     * @return array{mailer: string, host: ?string, port: ?int, scheme: string, username: ?string, has_password: bool, from_address: ?string, from_name: ?string}
     */
    public function mail(): array
    {
        $saved = $this->get(self::MAIL);

        return [
            'mailer' => $saved['mailer'] ?? (config('mail.default') === 'smtp' ? 'smtp' : 'log'),
            'host' => $saved['host'] ?? config('mail.mailers.smtp.host'),
            'port' => $saved['port'] ?? (int) config('mail.mailers.smtp.port'),
            'scheme' => $saved['scheme'] ?? (config('mail.mailers.smtp.scheme') === 'smtps' ? 'smtps' : 'smtp'),
            'username' => $saved['username'] ?? config('mail.mailers.smtp.username'),
            'has_password' => $saved ? ! empty($saved['password']) : filled(config('mail.mailers.smtp.password')),
            'from_address' => $saved['from_address'] ?? config('mail.from.address'),
            'from_name' => $saved['from_name'] ?? null,
        ];
    }

    /**
     * Saves the email settings; the password is stored encrypted (APP_KEY). A blank password keeps the saved one,
     * a blank username drops it.
     *
     * @param  array{mailer: string, host: ?string, port: ?int, scheme: ?string, username: ?string, password: ?string, from_address: string, from_name: ?string}  $mail
     */
    public function saveMail(array $mail): void
    {
        $password = match (true) {
            blank($mail['username'] ?? null) => null,
            filled($mail['password'] ?? null) => Crypt::encryptString($mail['password']),
            default => $this->get(self::MAIL)['password'] ?? null,
        };

        $this->set(self::MAIL, [...$mail, 'password' => $password]);
    }

    /** Puts the saved email settings into the mail config; nothing saved = the .env settings stay. */
    public function applyMailConfig(): void
    {
        $saved = $this->get(self::MAIL);

        if (! $saved) {
            return;
        }

        try {
            $password = $saved['password'] ? Crypt::decryptString($saved['password']) : null;
        } catch (DecryptException) {
            $password = null; // APP_KEY changed since it was saved – the password has to be entered again
        }

        config([
            'mail.default' => $saved['mailer'],
            'mail.mailers.smtp.host' => $saved['host'],
            'mail.mailers.smtp.port' => $saved['port'],
            'mail.mailers.smtp.scheme' => $saved['scheme'],
            'mail.mailers.smtp.username' => $saved['username'],
            'mail.mailers.smtp.password' => $password,
            'mail.from.address' => $saved['from_address'],
            'mail.from.name' => $saved['from_name'] ?: $this->companyName(),
        ]);
    }

    public function saveGeneral(string $companyName, string $dateFormat): void
    {
        $this->set(self::COMPANY_NAME, $companyName);
        $this->set(self::DATE_FORMAT, $dateFormat);
    }

    public function saveWorkWeek(WorkWeek $workWeek): void
    {
        $this->set(self::WORK_WEEK, $workWeek->toArray());
    }

    private function deleteLogoFile(?string $path): void
    {
        if ($path) {
            Storage::disk('branding')->delete($path);
        }
    }

    private function get(string $key): mixed
    {
        if (! array_key_exists($key, $this->loaded)) {
            // Wrapped so a missing setting is cached too (a cached null counts as a miss).
            $this->loaded[$key] = Cache::rememberForever(
                "settings.{$key}",
                fn () => ['value' => Setting::firstWhere('key', $key)?->value],
            )['value'];
        }

        return $this->loaded[$key];
    }

    /** Null removes the setting, so the default applies again. */
    private function set(string $key, mixed $value): void
    {
        $value === null
            ? Setting::where('key', $key)->delete()
            : Setting::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget("settings.{$key}");
        unset($this->loaded[$key]);
    }
}
