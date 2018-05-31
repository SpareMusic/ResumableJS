<?php

namespace SpareMusic\ResumableJS\Providers;

use SpareMusic\ResumableJS\Resumable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Request;
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
            ResumableRequestMakeCommand::class
        ]);

        $this->app->bind(Resumable::class, function ($app) {
            /** @var Request $request */
            $request = $app->make('request');

            return new Resumable($request);
        });
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Route::macro('resumable', function($uri, $action = null) {
            Route::get($uri, $action);
            Route::post($uri, $action);
        });
    }
}
