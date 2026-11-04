<?php

namespace App\Providers;

use App\Services\SettingService;
use App\Support\WorkWeek;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WorkWeek::class, fn ($app) => $app->make(SettingService::class)->workWeek());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        // Header bell: unread count + the latest few notifications.
        View::composer('layouts.header', function ($view) {
            $user = auth()->user();

            $view->with([
                'unreadCount' => $user->unreadNotifications()->count(),
                'notifications' => $user->notifications()->latest()->limit(config('hrms.header_notifications'))->get(),
            ]);
        });
    }
}
