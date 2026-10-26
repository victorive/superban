<?php

declare(strict_types=1);

namespace Victorive\Superban\Contracts;

interface MetricsRecorder
{
    /**
     * @param  array<string, string|int>  $tags
     */
    public function record(string $metric, array $tags = [], int $value = 1): void;
}
