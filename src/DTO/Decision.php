<?php

declare(strict_types=1);

namespace Victorive\Superban\DTO;

final readonly class Decision
{
    public function __construct(
        public bool $allowed,
        public ?Ban $ban = null,
        public ?Limit $limit = null,
        public int $remainingAttempts = 0,
    ) {}

    public static function allowed(int $remainingAttempts = 0): self
    {
        return new self(allowed: true, remainingAttempts: $remainingAttempts);
    }

    public static function banned(Ban $ban, ?Limit $limit = null): self
    {
        return new self(allowed: false, ban: $ban, limit: $limit);
    }
}
