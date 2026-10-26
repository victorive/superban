<?php

declare(strict_types=1);

namespace Victorive\Superban\Criteria;

use Illuminate\Http\Request;
use Victorive\Superban\Contracts\BanIdentityResolver;
use Victorive\Superban\DTO\Identity;

final class CompositeResolver implements BanIdentityResolver
{
    /**
     * @param  array<int, BanIdentityResolver>  $resolvers
     */
    public function __construct(private array $resolvers) {}

    public function resolve(Request $request): Identity
    {
        $parts = [];
        $labels = [];

        foreach ($this->resolvers as $resolver) {
            $identity = $resolver->resolve($request);
            $parts[] = "{$identity->type}:{$identity->value}";
            $labels[] = $identity->label ?? $identity->value;
        }

        return new Identity('composite', implode('|', $parts), implode('|', $labels));
    }

    public function name(): string
    {
        return 'composite';
    }
}
