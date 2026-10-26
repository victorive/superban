<?php

declare(strict_types=1);

namespace Victorive\Superban\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Victorive\Superban\Counters\CacheCounter;
use Victorive\Superban\Tests\TestCase;

final class CacheCounterTest extends TestCase
{
    public function test_counter_increments(): void
    {
        $counter = new CacheCounter(Cache::store('array'));

        $this->assertSame(1, $counter->hit('key', 60));
        $this->assertSame(2, $counter->hit('key', 60));
        $this->assertSame(3, $counter->hit('key', 60));
    }
}
