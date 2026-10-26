<?php

declare(strict_types=1);

namespace Victorive\Superban\Facades;

use Illuminate\Support\Facades\Facade;
use Victorive\Superban\SuperbanManager;

/**
 * @method static \Victorive\Superban\DTO\Limit limit(string $name, \Victorive\Superban\DTO\Limit $limit)
 * @method static \Victorive\Superban\DTO\Limit resolveLimit(string|array<int, string> $parameters)
 * @method static \Victorive\Superban\DTO\Decision enforce(\Illuminate\Http\Request $request, \Victorive\Superban\DTO\Limit $limit)
 * @method static \Victorive\Superban\DTO\Ban ban(\Victorive\Superban\DTO\Identity $identity, int $minutes, ?string $reason = null, ?string $scope = null)
 * @method static void unban(\Victorive\Superban\DTO\Identity $identity, ?string $scope = null)
 * @method static bool isBanned(\Victorive\Superban\DTO\Identity $identity, ?string $scope = null)
 * @method static \Victorive\Superban\DTO\Ban|null status(\Victorive\Superban\DTO\Identity $identity, ?string $scope = null)
 * @method static \Victorive\Superban\Contracts\BanStore store()
 * @method static \Victorive\Superban\Contracts\BanResponder responder()
 *
 * @see SuperbanManager
 */
final class Superban extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SuperbanManager::class;
    }
}
