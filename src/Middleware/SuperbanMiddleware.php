<?php

declare(strict_types=1);

namespace Victorive\Superban\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Victorive\Superban\DTO\Ban;
use Victorive\Superban\DTO\Limit;
use Victorive\Superban\SuperbanManager;

final class SuperbanMiddleware
{
    public function __construct(private SuperbanManager $superban) {}

    public function handle(Request $request, Closure $next, string $maxAttempts, string $decayMinutes = '1', string $bannedMinutes = '60'): Response
    {
        $limit = $this->superban->resolveLimit([$maxAttempts, $decayMinutes, $bannedMinutes]);

        $decision = $this->superban->enforce($request, $limit);

        if ($decision->allowed) {
            $response = $next($request);

            return $this->addRateLimitHeaders($response, $limit, $decision->remainingAttempts);
        }

        $ban = $decision->ban;

        if ($ban === null) {
            return $this->superban->responder()->limited($request, $limit, []);
        }

        return $this->superban->responder()->banned($request, $ban, $this->banHeaders($ban));
    }

    private function addRateLimitHeaders(Response $response, Limit $limit, int $remaining): Response
    {
        if (! config('superban.response.headers', true)) {
            return $response;
        }

        $response->headers->set('X-RateLimit-Limit', (string) $limit->maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function banHeaders(Ban $ban): array
    {
        if (! config('superban.response.headers', true)) {
            return [];
        }

        return [
            'Retry-After' => (string) $ban->retryAfter(),
            'X-Ban-Until' => (string) $ban->bannedUntil,
            'X-Ban-Scope' => $ban->scope ?? 'global',
        ];
    }
}
