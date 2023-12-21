<?php

namespace Victorive\Superban;

use Illuminate\Support\ServiceProvider;

class SuperbanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/superban.php' => config_path('superban.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
