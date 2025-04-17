<?php

namespace Funlifew\PushNotify;

use Funlifew\PushNotify\Console\Commands\GenerateToken;
use Funlifew\PushNotify\Console\Commands\InstallPush;
use Funlifew\PushNotify\Console\Commands\SendScheduledNotifications;
use Funlifew\PushNotify\Services\NotificationService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Facades\Session;

class PushNotifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register commands
        $this->commands([
            GenerateToken::class,
            InstallPush::class,
            SendScheduledNotifications::class,
        ]);

        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/config/push-notify.php', 'push-notify'
        );

        // Register helpers
        if (file_exists($helperFile = __DIR__ . '/helpers.php')) {
            require_once $helperFile;
        }

        // Register notification service
        $this->app->singleton('push-notify', function ($app) {
            return new NotificationService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/config/push-notify.php' => config_path('push-notify.php'),
        ], 'push-notify-config');

        // Publish assets
        $this->publishes([
            __DIR__ . '/../resources/js/sw.js' => public_path('sw.js'),
            __DIR__ . '/../resources/js/subscription.js' => public_path('vendor/push-notify/js/subscription.js'),
            __DIR__ . '/../resources/js/offline.html' => public_path('offline.html'),
            __DIR__ . '/../resources/images/default-icon.png' => public_path('default-icon.png'),
            __DIR__ . '/../resources/images/badge-icon.png' => public_path('badge-icon.png'),
        ], 'push-notify-assets');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/push-notify'),
        ], 'push-notify-views');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'push-notify');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // Configure Bootstrap pagination
        Paginator::useBootstrap();
        
        // CRITICAL FIX: Share errors variable with all views
        // This ensures $errors is always available and prevents "undefined variable" errors
        View::composer('*', function ($view) {
            if (!isset($view->errors)) {
                $errors = Session::get('errors', new ViewErrorBag);
                $view->with('errors', $errors);
            }
        });
        
        // Register scheduled tasks
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('push:send-scheduled')->everyMinute();
        });
        
        // Register middleware
        Route::aliasMiddleware('push-notify.cors', Http\Middlewares\AllowCorsMiddleware::class);
        Route::aliasMiddleware('push-notify.csrf', Http\Middlewares\DisableCsrfMiddleware::class);
    }
}