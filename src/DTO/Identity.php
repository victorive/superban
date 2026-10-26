<?php

declare(strict_types=1);

namespace Victorive\Superban\DTO;

final readonly class Identity
{
    public function __construct(
        public string $type,
        public string $value,
        public ?string $label = null,
    ) {}

    public function toKey(string $prefix, string $scope): string
    {
        return "{$prefix}:{$scope}:{$this->type}:".hash('sha256', $this->value);
    }
}
