<?php

declare(strict_types=1);

namespace Victorive\Superban\Criteria;

use Illuminate\Http\Request;
use Victorive\Superban\Contracts\BanIdentityResolver;
use Victorive\Superban\DTO\Identity;

final class ApiKeyResolver implements BanIdentityResolver
{
    public function __construct(private string $header = 'X-Api-Key') {}

    public function resolve(Request $request): Identity
    {
        $key = $request->header($this->header);

        if ($key === null) {
            return (new IpResolver)->resolve($request);
        }

        return new Identity('api_key', $key, substr($key, 0, 8));
    }

    public function name(): string
    {
        return 'api_key';
    }
}
