<?php

declare(strict_types=1);

namespace Victorive\Superban\Http\Responders;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Victorive\Superban\Contracts\BanResponder;
use Victorive\Superban\DTO\Ban;
use Victorive\Superban\DTO\Limit;

final class JsonResponder implements BanResponder
{
    /**
     * @param  array<string, string>  $headers
     */
    public function banned(Request $request, Ban $ban, array $headers): Response
    {
        return new JsonResponse([
            'message' => __('superban::superban.banned', [
                'until' => date('Y-m-d H:i:s', $ban->bannedUntil),
            ]),
            'banned_until' => $ban->bannedUntil,
        ], config('superban.response.status', 429), $headers);
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function limited(Request $request, Limit $limit, array $headers): Response
    {
        return new JsonResponse([
            'message' => __('superban::superban.limited'),
        ], config('superban.response.status', 429), $headers);
    }
}
