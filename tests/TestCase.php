<?php

declare(strict_types=1);

namespace Victorive\Superban\Tests;

use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Victorive\Superban\SuperbanServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::purge();
        Cache::store('array')->flush();
    }

    protected function getPackageProviders($app): array
    {
        return [SuperbanServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('superban.store', 'array');
        $app['config']->set('superban.events', false);
    }
}
