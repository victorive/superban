<?php

declare(strict_types=1);

namespace Victorive\Superban;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Victorive\Superban\Contracts\BanIdentityResolver;
use Victorive\Superban\Contracts\BanResponder;
use Victorive\Superban\Contracts\BanStore;
use Victorive\Superban\Contracts\Counter;
use Victorive\Superban\Contracts\MetricsRecorder;
use Victorive\Superban\Counters\CacheCounter;
use Victorive\Superban\Criteria\ResolverFactory;
use Victorive\Superban\Http\Responders\ResponderFactory;
use Victorive\Superban\Metrics\NullMetricsRecorder;
use Victorive\Superban\Middleware\SuperbanMiddleware;
use Victorive\Superban\Stores\CacheBanStore;
use Victorive\Superban\Stores\DatabaseBanStore;

final class SuperbanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/superban.php', 'superban');

        $this->app->singleton(SuperbanManager::class, function ($app) {
            $config = $app->make('config')->get('superban');

            $storeName = $config['store'] ?? null;
            $cache = Cache::store($storeName);

            return new SuperbanManager(
                $config,
                $app->make(BanIdentityResolver::class),
                $app->make(BanStore::class),
                $app->make(Counter::class),
                $app->make(BanResponder::class),
                $app->make(Dispatcher::class),
                $app->make(MetricsRecorder::class),
                $cache,
            );
        });

        $this->app->bind(BanIdentityResolver::class, function ($app) {
            return ResolverFactory::make($app->make('config')->get('superban.criteria', 'ip'));
        });

        $this->app->bind(BanStore::class, function ($app) {
            $config = $app->make('config')->get('superban');

            if ($config['audit']['enabled'] ?? false) {
                return new DatabaseBanStore(
                    $app->make('db')->connection($config['audit']['connection'] ?? null),
                    $config['audit']['table'] ?? 'superban_bans',
                );
            }

            return new CacheBanStore(Cache::store($config['store'] ?? null));
        });

        $this->app->bind(Counter::class, function ($app) {
            $config = $app->make('config')->get('superban');

            return new CacheCounter(Cache::store($config['store'] ?? null));
        });

        $this->app->bind(BanResponder::class, function ($app) {
            return ResponderFactory::make($app->make('config')->get('superban.response.format', 'json'));
        });

        $this->app->bind(MetricsRecorder::class, NullMetricsRecorder::class);
    }

    public function boot(Registrar $router): void
    {
        $this->publishes([
            __DIR__.'/../config/superban.php' => config_path('superban.php'),
        ], 'superban-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'superban-migrations');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'superban');
        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/superban'),
        ], 'superban-lang');

        $router->aliasMiddleware('superban', SuperbanMiddleware::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\BanCommand::class,
                Console\Commands\UnbanCommand::class,
                Console\Commands\StatusCommand::class,
                Console\Commands\ListCommand::class,
                Console\Commands\PruneCommand::class,
            ]);
        }
    }
}
