<?php

namespace Victorive\Superban\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Victorive\Superban\Exceptions\InvalidBanCriteriaException;

class SuperbanMiddleware
{
    private const KEY_PREFIX = 'superban:';

    /**
     * @throws InvalidArgumentException
     */
    public function handle(Request $request, Closure $next, int $maxAttempts, int $decayMinutes, int $bannedMinutes): Response
    {
        $banCriteria = $this->getBanCriteria($request);
        $key = self::KEY_PREFIX.$banCriteria;

        $bannedUntil = $this->getBannedUntil($key);

        if ($bannedUntil !== null && Carbon::now()->timestamp < $bannedUntil) {
            return $this->respondWithError($bannedUntil);
        }

        $attempts = RateLimiter::hit($key, $decayMinutes * 60);

        if ($attempts > $maxAttempts) {
            $bannedUntil = Carbon::now()->addMinutes($bannedMinutes)->timestamp;

            $cacheDriver = config('superban.cache_driver');

            Cache::driver($cacheDriver)->put($key, $bannedUntil, $bannedMinutes * 60);
            RateLimiter::clear($key);

            return $this->respondWithError($bannedUntil);
        }

        return $next($request);
    }

    private function getBanCriteria(Request $request): string
    {
        $banCriteriaType = config('superban.ban_criteria');

        return match ($banCriteriaType) {
            'user_id' => (string) ($request->user()?->id ?? $this->fallbackIdentifier($request)),
            'email' => $request->user()?->email ?? $this->fallbackIdentifier($request),
            'ip' => $request->ip() ?? $this->fallbackIdentifier($request),
            default => throw new InvalidBanCriteriaException('The provided ban criteria is invalid.')
        };
    }

    private function fallbackIdentifier(Request $request): string
    {
        return $request->ip() ?? 'unknown';
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getBannedUntil(string $key): ?int
    {
        $cacheDriver = config('superban.cache_driver');
        $value = Cache::driver($cacheDriver)->get($key);

        return $value === null ? null : (int) $value;
    }

    private function respondWithError(int $bannedUntil): JsonResponse
    {
        $retryAfter = max(0, $bannedUntil - Carbon::now()->timestamp);

        return response()->json([
            'message' => 'You have been banned until '.Carbon::createFromTimestamp($bannedUntil)->toDateTimeString(),
        ], Response::HTTP_TOO_MANY_REQUESTS)->header('Retry-After', $retryAfter);
    }
}
