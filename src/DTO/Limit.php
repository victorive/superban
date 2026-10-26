<?php

declare(strict_types=1);

namespace Victorive\Superban\DTO;

final readonly class Limit
{
    public function __construct(
        public string $name,
        public int $maxAttempts,
        public int $decayMinutes,
        public int $banMinutes,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $name, array $config): self
    {
        return new self(
            $name,
            (int) ($config['max_attempts'] ?? 60),
            (int) ($config['decay_minutes'] ?? 1),
            (int) ($config['ban_minutes'] ?? 60),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'max_attempts' => $this->maxAttempts,
            'decay_minutes' => $this->decayMinutes,
            'ban_minutes' => $this->banMinutes,
        ];
    }
}
