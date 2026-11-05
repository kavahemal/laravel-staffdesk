<?php

namespace App\Providers;

use App\Services\SettingService;
use App\Support\WorkWeek;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Scoped, not singleton: a queue worker gets a fresh instance per job, so saved changes are seen.
        $this->app->scoped(SettingService::class);
        $this->app->bind(WorkWeek::class, fn ($app) => $app->make(SettingService::class)->workWeek());
        // Email settings from the Settings page, applied only when something is about to be mailed.
        $this->app->afterResolving('mail.manager', fn () => $this->app->make(SettingService::class)->applyMailConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        View::composer('layouts.header', fn ($view) => $view->with(request()->user()->notificationBell()));
        View::composer(['layouts.app', 'layouts.guest', 'layouts.header', 'layouts.sidebar'], fn ($view) => $view->with([
            'companyName' => app(SettingService::class)->companyName(),
            'logoUrl' => app(SettingService::class)->logoUrl(),
        ]));

        // Deactivating deletes tokens too; this also covers a user switched off directly in the database.
        Sanctum::authenticateAccessTokensUsing(fn (PersonalAccessToken $token, bool $isValid) => $isValid && $token->tokenable?->is_active);

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(config('hrms.api_rate_limit'))
            ->by($request->user()?->id ?: $request->ip()));

        // Per email + IP, so one attacker can't lock out an account from everywhere.
        RateLimiter::for('api-login', fn (Request $request) => Limit::perMinute(config('hrms.api_login_rate_limit'))
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));
    }
}
