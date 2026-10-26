<?php

declare(strict_types=1);

namespace Victorive\Superban\Contracts;

interface Counter
{
    public function hit(string $key, int $decaySeconds): int;

    public function attempts(string $key): int;

    public function clear(string $key): void;
}
