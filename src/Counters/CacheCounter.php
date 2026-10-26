<?php

declare(strict_types=1);

namespace Victorive\Superban\Counters;

use Illuminate\Contracts\Cache\Repository;
use Victorive\Superban\Contracts\Counter;

final class CacheCounter implements Counter
{
    public function __construct(private Repository $cache) {}

    public function hit(string $key, int $decaySeconds): int
    {
        $this->cache->add($key, 0, $decaySeconds);

        $attempts = $this->cache->increment($key);

        return $attempts === false ? 1 : (int) $attempts;
    }

    public function attempts(string $key): int
    {
        return (int) $this->cache->get($key, 0);
    }

    public function clear(string $key): void
    {
        $this->cache->forget($key);
    }
}
