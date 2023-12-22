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
    /**
     * @throws InvalidArgumentException
     */
    public function handle(Request $request, Closure $next, int $maxAttempts, int $decayMinutes, int $bannedMinutes): Response
    {
        $banCriteria = $this->getBanCriteria($request);
        $bannedUntil = $this->getBannedUntil($banCriteria);

        if ($bannedUntil && (Carbon::now()->timestamp < $bannedUntil)) {
            return $this->respondWithError($bannedUntil);
        }

        if (RateLimiter::tooManyAttempts($banCriteria, $maxAttempts)) {
            $bannedUntil = Carbon::now()->addMinutes($bannedMinutes)->timestamp;

            $cacheDriver = config('superban.cache_driver');

            Cache::driver($cacheDriver)->put($banCriteria, $bannedUntil, $bannedMinutes * 60);

            return $this->respondWithError($bannedUntil);
        }

        if (! $bannedUntil || (Carbon::now()->timestamp > $bannedUntil)) {
            RateLimiter::hit($banCriteria, $decayMinutes * 60);
        }

        return $next($request);
    }

    private function getBanCriteria(Request $request)
    {
        $banCriteriaType = config('superban.ban_criteria');

        return match ($banCriteriaType) {
            'user_id' => $request->user() ? $request->user()->id : null,
            'email' => $request->user() ? $request->user()->email : null,
            'ip' => $request->ip(),
            default => throw new InvalidBanCriteriaException('The provided ban criteria is invalid.')
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getBannedUntil(string $banCriteria): ?int
    {
        $cacheDriver = config('superban.cache_driver');

        return Cache::driver($cacheDriver)->get($banCriteria);
    }

    private function respondWithError(int $bannedUntil): JsonResponse
    {
        return response()->json([
            'message' => 'You have been banned until '.Carbon::createFromTimestamp($bannedUntil)->toDateTimeString(),
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }
}
