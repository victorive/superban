<?php

declare(strict_types=1);

namespace Victorive\Superban\Console\Commands;

use Illuminate\Console\Command;
use Victorive\Superban\Console\Commands\Concerns\InteractsWithArguments;
use Victorive\Superban\DTO\Identity;
use Victorive\Superban\Facades\Superban;

final class BanCommand extends Command
{
    use InteractsWithArguments;

    protected $signature = 'superban:ban
                            {identity : The identity to ban, e.g. user:42, ip:1.2.3.4, email:test@example.com, api_key:token}
                            {--minutes=60 : Ban duration in minutes}
                            {--reason=manual : Reason for the ban}
                            {--scope=global : Ban scope}';

    protected $description = 'Ban a client by identity';

    public function handle(): int
    {
        $identity = $this->parseIdentity($this->stringArgument('identity'));
        $minutes = (int) $this->option('minutes');
        $reason = $this->stringOption('reason');
        $scope = $this->stringOption('scope');

        $ban = Superban::ban($identity, $minutes, $reason, $scope);

        $this->info("Banned [{$identity->label}] until ".date('Y-m-d H:i:s', $ban->bannedUntil));

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
