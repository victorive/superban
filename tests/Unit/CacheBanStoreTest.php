<?php

declare(strict_types=1);

namespace Victorive\Superban\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Victorive\Superban\Stores\CacheBanStore;
use Victorive\Superban\Tests\TestCase;

final class CacheBanStoreTest extends TestCase
{
    public function test_flush_is_not_supported(): void
    {
        $store = new CacheBanStore(Cache::store('array'));

        $this->expectException(RuntimeException::class);

        $store->flush();
    }
}
