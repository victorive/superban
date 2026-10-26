<?php

declare(strict_types=1);

namespace Victorive\Superban\Criteria;

use Illuminate\Http\Request;
use Victorive\Superban\Contracts\BanIdentityResolver;
use Victorive\Superban\DTO\Identity;

final class IpResolver implements BanIdentityResolver
{
    public function resolve(Request $request): Identity
    {
        $ip = $request->ip();

        return new Identity('ip', $ip ?? 'unknown', $ip);
    }

    public function name(): string
    {
        return 'ip';
    }
}
