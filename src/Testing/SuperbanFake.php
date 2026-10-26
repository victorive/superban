<?php

declare(strict_types=1);

namespace Victorive\Superban\Testing;

use PHPUnit\Framework\Assert;
use Victorive\Superban\Contracts\BanStore;
use Victorive\Superban\Contracts\Counter;
use Victorive\Superban\DTO\Ban;
use Victorive\Superban\DTO\Identity;

final class SuperbanFake implements BanStore, Counter
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $bans = [];

    /**
     * @var array<string, int>
     */
    private array $counters = [];

    public function get(string $key): ?Ban
    {
        if (! isset($this->bans[$key])) {
            return null;
        }

        $data = $this->bans[$key];

        return new Ban(
            $key,
            new Identity((string) $data['type'], (string) $data['value']),
            (int) $data['banned_until'],
            (int) $data['banned_at'],
            (int) $data['strikes'],
            $data['reason'] ?? null,
            $data['scope'] ?? null,
        );
    }

    public function put(string $key, Ban $ban): void
    {
        $this->bans[$key] = $ban->toArray();
    }

    public function forget(string $key): void
    {
        unset($this->bans[$key]);
    }

    public function flush(): void
    {
        $this->bans = [];
        $this->counters = [];
    }

    /**
     * @return iterable<int, Ban>
     */
    public function all(): iterable
    {
        foreach ($this->bans as $key => $data) {
            $ban = $this->get($key);

            if ($ban !== null) {
                yield $ban;
            }
        }
    }

    public function hit(string $key, int $decaySeconds): int
    {
        $this->counters[$key] = ($this->counters[$key] ?? 0) + 1;

        return $this->counters[$key];
    }

    public function attempts(string $key): int
    {
        return $this->counters[$key] ?? 0;
    }

    public function clear(string $key): void
    {
        unset($this->counters[$key]);
    }

    public function assertBanned(Identity $identity, ?string $scope = null, ?string $message = null): void
    {
        $scope ??= 'global';
        $key = $identity->toKey(config('superban.prefix', 'superban'), $scope);
        $message ??= "Expected [{$identity->label}] to be banned.";

        Assert::assertTrue($this->isBannedKey($key), $message);
    }

    public function assertNotBanned(Identity $identity, ?string $scope = null, ?string $message = null): void
    {
        $scope ??= 'global';
        $key = $identity->toKey(config('superban.prefix', 'superban'), $scope);
        $message ??= "Expected [{$identity->label}] not to be banned.";

        Assert::assertFalse($this->isBannedKey($key), $message);
    }

    public function assertBannedUntil(Identity $identity, int $timestamp, ?string $scope = null, ?string $message = null): void
    {
        $scope ??= 'global';
        $key = $identity->toKey(config('superban.prefix', 'superban'), $scope);
        $ban = $this->get($key);
        $message ??= "Expected [{$identity->label}] to be banned.";

        Assert::assertNotNull($ban, $message);
        Assert::assertEquals($timestamp, $ban->bannedUntil, $message);
    }

    public function assertStrikes(Identity $identity, int $expected, ?string $scope = null, ?string $message = null): void
    {
        $scope ??= 'global';
        $key = $identity->toKey(config('superban.prefix', 'superban'), $scope);
        $ban = $this->get($key);
        $message ??= "Expected [{$identity->label}] to be banned.";

        Assert::assertNotNull($ban, $message);
        Assert::assertEquals($expected, $ban->strikes, $message);
    }

    private function isBannedKey(string $key): bool
    {
        $ban = $this->get($key);

        return $ban !== null && ! $ban->expired();
    }
}
