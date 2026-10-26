<?php

declare(strict_types=1);

namespace Victorive\Superban\Events;

use Illuminate\Http\Request;
use Victorive\Superban\DTO\Ban;
use Victorive\Superban\DTO\Limit;

final class ClientBanned
{
    public function __construct(
        public readonly Ban $ban,
        public readonly ?Limit $limit,
        public readonly ?Request $request,
    ) {}
}
