<?php

declare(strict_types=1);

namespace Victorive\Superban\Contracts;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Victorive\Superban\DTO\Ban;
use Victorive\Superban\DTO\Limit;

interface BanResponder
{
    /**
     * @param  array<string, string>  $headers
     */
    public function banned(Request $request, Ban $ban, array $headers): Response;

    /**
     * @param  array<string, string>  $headers
     */
    public function limited(Request $request, Limit $limit, array $headers): Response;
}
