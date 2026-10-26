<?php

declare(strict_types=1);

namespace Victorive\Superban\Criteria;

use Illuminate\Http\Request;
use Victorive\Superban\Contracts\BanIdentityResolver;
use Victorive\Superban\DTO\Identity;

final class UserIdResolver implements BanIdentityResolver
{
    public function resolve(Request $request): Identity
    {
        $id = $request->user()?->id;

        if ($id === null) {
            return (new IpResolver)->resolve($request);
        }

        return new Identity('user_id', (string) $id, (string) $id);
    }

    public function name(): string
    {
        return 'user_id';
    }
}
