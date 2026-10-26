<?php

declare(strict_types=1);

namespace Victorive\Superban\Events;

use Victorive\Superban\DTO\Identity;

final class BanExpired
{
    public function __construct(
        public readonly Identity $identity,
        public readonly int $expiredAt,
    ) {}
}
