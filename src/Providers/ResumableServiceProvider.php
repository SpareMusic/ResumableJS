<?php

namespace SpareMusic\ResumableJS\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use SpareMusic\ResumableJS\Resumable;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Symfony\Component\HttpFoundation\Request;
use SpareMusic\ResumableJS\Commands\ClearChunksCommand;
use SpareMusic\ResumableJS\Commands\ResumableRequestMakeCommand;

class ResumableServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register the commands
        $this->commands([
            ClearChunksCommand::class,
            ResumableRequestMakeCommand::class,
        ]);

        // Register config
        $this->registerConfig();

        $this->app->bind(Resumable::class, function ($app) {
            /** @var Request $request */
            $request = $app->make('request');

            return new Resumable($request);
        });

        $this->app->resolving(Resumable::class, function ($resumable, $app) {
            $resumable->setup();
        });
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Route::macro('resumable', function ($uri, $action = null) {
            Route::get($uri, $action);
            Route::post($uri, $action);
        });

        // Get the schedule config
        $scheduleConfig = config("resumable-js.schedule", []);

        // Run only if schedule is enabled
        if (Arr::get($scheduleConfig, "enabled", false) === true) {
            // Wait until the app is fully booted
            $this->app->booted(function () use ($scheduleConfig) {
                $schedule = $this->app->make(Schedule::class);

                // Register the clear chunks cron
                $schedule->command('chunks:clear')->cron(Arr::get($scheduleConfig, "cron", "* * * * *"));
            });

        }
    }

    protected function registerConfig()
    {
        // Config file
        $configPath = __DIR__.'/../../config/resumable-js.php';

        // Publish the config
        $this->publishes([
            $configPath => config_path('resumable-js.php'),
        ]);

        // Merge the default config to prevent any crash or unfilled configs
        $this->mergeConfigFrom(
            $configPath,
            'resumable-js'
        );
    }
}
