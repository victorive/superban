<?php

declare(strict_types=1);

namespace Victorive\Superban\Events;

use Illuminate\Http\Request;
use Victorive\Superban\DTO\Identity;
use Victorive\Superban\DTO\Limit;

final class LimitExceeded
{
    public function __construct(
        public readonly Identity $identity,
        public readonly Limit $limit,
        public readonly ?Request $request,
    ) {}
}
