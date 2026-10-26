<?php

declare(strict_types=1);

namespace Victorive\Superban\Metrics;

use Victorive\Superban\Contracts\MetricsRecorder;

final class NullMetricsRecorder implements MetricsRecorder
{
    /**
     * @param  array<string, string|int>  $tags
     */
    public function record(string $metric, array $tags = [], int $value = 1): void
    {
        // no-op
    }
}
