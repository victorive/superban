<?php

declare(strict_types=1);

namespace Victorive\Superban\DTO;

use Illuminate\Support\Carbon;

final readonly class Ban
{
    public function __construct(
        public string $key,
        public Identity $identity,
        public int $bannedUntil,
        public int $bannedAt,
        public int $strikes,
        public ?string $reason = null,
        public ?string $scope = null,
        public ?string $route = null,
    ) {}

    public function expired(): bool
    {
        return (int) Carbon::now()->timestamp >= $this->bannedUntil;
    }

    public function retryAfter(): int
    {
        return max(0, $this->bannedUntil - (int) Carbon::now()->timestamp);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->identity->type,
            'value' => $this->identity->value,
            'banned_until' => $this->bannedUntil,
            'banned_at' => $this->bannedAt,
            'strikes' => $this->strikes,
            'reason' => $this->reason,
            'scope' => $this->scope,
            'route' => $this->route,
        ];
    }
}
