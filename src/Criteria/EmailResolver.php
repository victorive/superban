<?php

declare(strict_types=1);

namespace Victorive\Superban\Criteria;

use Illuminate\Http\Request;
use Victorive\Superban\Contracts\BanIdentityResolver;
use Victorive\Superban\DTO\Identity;

final class EmailResolver implements BanIdentityResolver
{
    public function resolve(Request $request): Identity
    {
        $email = data_get($request->user(), 'email');

        if (! is_string($email) || $email === '') {
            return (new IpResolver)->resolve($request);
        }

        return new Identity('email', $email, $email);
    }

    public function name(): string
    {
        return 'email';
    }
}
