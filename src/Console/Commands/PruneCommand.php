<?php

declare(strict_types=1);

namespace Victorive\Superban\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class PruneCommand extends Command
{
    protected $signature = 'superban:prune {--days=90 : Retention period in days}';

    protected $description = 'Remove expired ban records from the database audit store';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days)->timestamp;

        $config = config('superban.audit');

        if (! ($config['enabled'] ?? false)) {
            $this->error('Pruning requires the database audit store to be enabled.');

            return self::FAILURE;
        }

        $deleted = DB::connection($config['connection'] ?? null)
            ->table($config['table'] ?? 'superban_bans')
            ->where('banned_until', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} expired ban record(s).");

        return self::SUCCESS;
    }
}
