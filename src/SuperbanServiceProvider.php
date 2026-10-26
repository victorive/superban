<?php

namespace Victorive\Superban;

use Illuminate\Support\ServiceProvider;
use Victorive\Superban\Middleware\SuperbanMiddleware;

class SuperbanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/superban.php', 'superban');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/superban.php' => config_path('superban.php'),
        ], 'superban-config');

        $this->app['router']->aliasMiddleware('superban', SuperbanMiddleware::class);
    }
}
