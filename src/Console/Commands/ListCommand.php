<?php

declare(strict_types=1);

namespace Victorive\Superban\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use Victorive\Superban\Facades\Superban;

final class ListCommand extends Command
{
    protected $signature = 'superban:list {--scope= : Filter by scope}';

    protected $description = 'List active bans (requires database audit store)';

    public function handle(): int
    {
        try {
            $bans = Superban::store()->all();
        } catch (RuntimeException $e) {
            $this->error('Active ban listing requires the database audit store to be enabled.');

            return self::FAILURE;
        }

        $rows = [];
        foreach ($bans as $ban) {
            if ($this->option('scope') !== null && $ban->scope !== $this->option('scope')) {
                continue;
            }

            $rows[] = [
                $ban->identity->type,
                $ban->identity->label,
                $ban->scope ?? 'global',
                $ban->reason ?? '-',
                $ban->strikes,
                date('Y-m-d H:i:s', $ban->bannedUntil),
            ];
        }

        $this->table(['Type', 'Value', 'Scope', 'Reason', 'Strikes', 'Banned until'], $rows);

        return self::SUCCESS;
    }
}
