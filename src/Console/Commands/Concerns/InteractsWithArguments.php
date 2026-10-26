<?php

declare(strict_types=1);

namespace Victorive\Superban\Console\Commands\Concerns;

trait InteractsWithArguments
{
    private function stringArgument(string $key): string
    {
        $value = $this->argument($key);
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return (string) $value;
    }

    private function stringOption(string $key): string
    {
        $value = $this->option($key);
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return (string) $value;
    }
}
