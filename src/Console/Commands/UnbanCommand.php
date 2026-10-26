<?php

declare(strict_types=1);

namespace Victorive\Superban\Console\Commands;

use Illuminate\Console\Command;
use Victorive\Superban\Console\Commands\Concerns\InteractsWithArguments;
use Victorive\Superban\DTO\Identity;
use Victorive\Superban\Facades\Superban;

final class UnbanCommand extends Command
{
    use InteractsWithArguments;

    protected $signature = 'superban:unban
                            {identity : The identity to unban, e.g. user:42, ip:1.2.3.4}
                            {--scope=global : Ban scope}';

    protected $description = 'Lift an active ban';

    public function handle(): int
    {
        $identity = $this->parseIdentity($this->stringArgument('identity'));
        $scope = $this->stringOption('scope');

        Superban::unban($identity, $scope);

        $this->info("Unbanned [{$identity->label}]");

        return self::SUCCESS;
    }

    private function parseIdentity(string $input): Identity
    {
        $parts = explode(':', $input, 2) + [null, null];
        $type = $parts[0] ?? null;
        $value = $parts[1] ?? null;

        return match ($type) {
            'user' => new Identity('user_id', (string) $value, (string) $value),
            'ip' => new Identity('ip', (string) $value, (string) $value),
            'email' => new Identity('email', (string) $value, (string) $value),
            'api_key' => new Identity('api_key', (string) $value, substr((string) $value, 0, 8)),
            default => new Identity('unknown', $input, $input),
        };
    }
}
