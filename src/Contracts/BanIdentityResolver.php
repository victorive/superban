<?php

declare(strict_types=1);

namespace Victorive\Superban\Contracts;

use Illuminate\Http\Request;
use Victorive\Superban\DTO\Identity;

interface BanIdentityResolver
{
    public function resolve(Request $request): Identity;

    public function name(): string;
}
