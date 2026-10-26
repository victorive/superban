<?php

declare(strict_types=1);

namespace Victorive\Superban\Stores;

use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Victorive\Superban\Contracts\BanStore;
use Victorive\Superban\DTO\Ban;
use Victorive\Superban\DTO\Identity;

final class DatabaseBanStore implements BanStore
{
    public function __construct(
        private Connection $db,
        private string $table,
    ) {}

    public function get(string $key): ?Ban
    {
        $row = $this->db->table($this->table)
            ->where('key', $key)
            ->where('banned_until', '>', Carbon::now()->timestamp)
            ->whereNull('lifted_at')
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->rowToBan($row);
    }

    public function put(string $key, Ban $ban): void
    {
        $this->db->table($this->table)->updateOrInsert(
            ['key' => $key],
            [
                'type' => $ban->identity->type,
                'value' => $ban->identity->value,
                'scope' => $ban->scope,
                'route' => $ban->route,
                'reason' => $ban->reason,
                'strikes' => $ban->strikes,
                'banned_at' => Carbon::createFromTimestamp($ban->bannedAt),
                'banned_until' => Carbon::createFromTimestamp($ban->bannedUntil),
                'lifted_at' => null,
            ]
        );
    }

    public function forget(string $key): void
    {
        $this->db->table($this->table)
            ->where('key', $key)
            ->update(['lifted_at' => Carbon::now()]);
    }

    public function flush(): void
    {
        $this->db->table($this->table)->delete();
    }

    public function all(): iterable
    {
        $rows = $this->db->table($this->table)
            ->where('banned_until', '>', Carbon::now()->timestamp)
            ->whereNull('lifted_at')
            ->get();

        foreach ($rows as $row) {
            yield $this->rowToBan($row);
        }
    }

    private function rowToBan(\stdClass $row): Ban
    {
        return new Ban(
            (string) $row->key,
            new Identity((string) $row->type, (string) $row->value),
            (int) $row->banned_until,
            (int) $row->banned_at,
            (int) $row->strikes,
            $row->reason ?? null,
            $row->scope ?? null,
            $row->route ?? null,
        );
    }
}
