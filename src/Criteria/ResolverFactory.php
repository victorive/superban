<?php

declare(strict_types=1);

namespace Victorive\Superban\Criteria;

use Victorive\Superban\Contracts\BanIdentityResolver;
use Victorive\Superban\Exceptions\InvalidConfigurationException;

final class ResolverFactory
{
    /**
     * @param  string|array<int, string>  $criteria
     */
    public static function make(string|array $criteria): BanIdentityResolver
    {
        $criteria = is_array($criteria) ? $criteria : explode(',', $criteria);
        $criteria = array_map('trim', $criteria);

        if (count($criteria) === 1) {
            return self::resolverFor($criteria[0]);
        }

        return new CompositeResolver(array_map(fn (string $c) => self::resolverFor($c), $criteria));
    }

    private static function resolverFor(string $criteria): BanIdentityResolver
    {
        return match ($criteria) {
            'ip' => new IpResolver,
            'user_id' => new UserIdResolver,
            'email' => new EmailResolver,
            'api_key' => new ApiKeyResolver,
            default => throw new InvalidConfigurationException("Unsupported ban criteria [{$criteria}]."),
        };
    }
}
