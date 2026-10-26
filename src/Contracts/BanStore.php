<?php

declare(strict_types=1);

namespace Victorive\Superban\Contracts;

use Victorive\Superban\DTO\Ban;

interface BanStore
{
    public function get(string $key): ?Ban;

    public function put(string $key, Ban $ban): void;

    public function forget(string $key): void;

    public function flush(): void;

    /**
     * @return iterable<Ban>
     *
     * @throws \RuntimeException if the store does not support enumeration
     */
    public function all(): iterable;
}
