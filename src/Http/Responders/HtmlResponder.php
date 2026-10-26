<?php

declare(strict_types=1);

namespace Victorive\Superban\Http\Responders;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Victorive\Superban\Contracts\BanResponder;
use Victorive\Superban\DTO\Ban;
use Victorive\Superban\DTO\Limit;

final class HtmlResponder implements BanResponder
{
    /**
     * @param  array<string, string>  $headers
     */
    public function banned(Request $request, Ban $ban, array $headers): Response
    {
        return new Response(
            '<h1>'.__('superban::superban.banned', ['until' => date('Y-m-d H:i:s', $ban->bannedUntil)]).'</h1>',
            config('superban.response.status', 429),
            array_merge($headers, ['Content-Type' => 'text/html'])
        );
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function limited(Request $request, Limit $limit, array $headers): Response
    {
        return new Response(
            '<h1>'.__('superban::superban.limited').'</h1>',
            config('superban.response.status', 429),
            array_merge($headers, ['Content-Type' => 'text/html'])
        );
    }
}
