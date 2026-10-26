<?php

declare(strict_types=1);

namespace Victorive\Superban\Stores;

use Illuminate\Contracts\Cache\Repository;
use RuntimeException;
use Victorive\Superban\Contracts\BanStore;
use Victorive\Superban\DTO\Ban;
use Victorive\Superban\DTO\Identity;

final class CacheBanStore implements BanStore
{
    public function __construct(private Repository $cache) {}

    public function get(string $key): ?Ban
    {
        $value = $this->cache->get($key);

        if (! is_array($value)) {
            return null;
        }

        return $this->arrayToBan($key, $value);
    }

    public function put(string $key, Ban $ban): void
    {
        $ttl = max(1, $ban->bannedUntil - time());

        $this->cache->put($key, $ban->toArray(), $ttl);
    }

    public function forget(string $key): void
    {
        $this->cache->forget($key);
    }

    public function flush(): void
    {
        throw new RuntimeException('The cache ban store does not support flushing.');
    }

    public function all(): iterable
    {
        throw new RuntimeException('The cache ban store does not support enumeration.');
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function arrayToBan(string $key, array $value): Ban
    {
        return new Ban(
            $key,
            new Identity((string) ($value['type'] ?? 'unknown'), (string) ($value['value'] ?? 'unknown')),
            (int) $value['banned_until'],
            (int) $value['banned_at'],
            (int) ($value['strikes'] ?? 1),
            $value['reason'] ?? null,
            $value['scope'] ?? null,
            $value['route'] ?? null,
        );
    }
}
