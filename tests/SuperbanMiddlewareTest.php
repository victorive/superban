<?php

namespace Victorive\Superban\Tests;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Response;
use Victorive\Superban\Exceptions\InvalidBanCriteriaException;
use Victorive\Superban\Middleware\SuperbanMiddleware;

class SuperbanMiddlewareTest extends TestCase
{
    protected SuperbanMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'array');
        Config::set('superban.cache_driver', 'array');
        Cache::flush();
        Carbon::setTestNow(Carbon::now());

        $this->middleware = new SuperbanMiddleware();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function requestWithUser(?stdClass $user = null): Request
    {
        $request = Request::create('/thisroute');
        if ($user !== null) {
            $request->setUserResolver(fn () => $user);
        }

        return $request;
    }

    public static function banCriteriaDataProvider(): array
    {
        return [
            ['user_id'],
            ['email'],
            ['ip'],
        ];
    }

    public function testAllowedRequestPasses(): void
    {
        Config::set('superban.ban_criteria', 'ip');

        $request = $this->requestWithUser();
        $response = $this->middleware->handle($request, fn () => new Response('ok'), 2, 1, 5);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @dataProvider banCriteriaDataProvider
     */
    public function testGuestDoesNotCrashWithAnyCriteria(string $banCriteria): void
    {
        Config::set('superban.ban_criteria', $banCriteria);

        $request = $this->requestWithUser();
        $response = $this->middleware->handle($request, fn () => new Response('ok'), 2, 1, 5);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testAuthenticatedUserCriteriaWork(): void
    {
        Config::set('superban.ban_criteria', 'user_id');

        $user = new stdClass();
        $user->id = 1;
        $user->email = 'test@example.com';

        $request = $this->requestWithUser($user);
        $response = $this->middleware->handle($request, fn () => new Response('ok'), 2, 1, 5);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testBannedUserIsDeniedAccess(): void
    {
        Config::set('superban.ban_criteria', 'ip');

        $request = $this->requestWithUser();
        $bannedUntil = Carbon::now()->addMinutes(5)->timestamp;
        Cache::put('superban:'.$request->ip(), $bannedUntil, 5);

        $response = $this->middleware->handle($request, fn () => new Response('ok'), 2, 1, 5);

        $this->assertEquals(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Retry-After'));
    }

    public function testUserExceedingRateLimitIsBanned(): void
    {
        Config::set('superban.ban_criteria', 'ip');

        $request = $this->requestWithUser();
        $maxAttempts = 2;
        $decayMinutes = 1;
        $bannedMinutes = 5;

        $this->middleware->handle($request, fn () => new Response('ok'), $maxAttempts, $decayMinutes, $bannedMinutes);
        $this->middleware->handle($request, fn () => new Response('ok'), $maxAttempts, $decayMinutes, $bannedMinutes);

        $response = $this->middleware->handle($request, fn () => new Response('ok'), $maxAttempts, $decayMinutes, $bannedMinutes);

        $this->assertEquals(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertStringContainsString('You have been banned until', $response->getContent());
        $this->assertTrue($response->headers->has('Retry-After'));
    }

    public function testUserCanAccessRouteAfterBanTimeExpires(): void
    {
        Config::set('superban.ban_criteria', 'ip');

        $request = $this->requestWithUser();
        $maxAttempts = 2;
        $decayMinutes = 1;
        $bannedMinutes = 5;

        $this->middleware->handle($request, fn () => new Response('ok'), $maxAttempts, $decayMinutes, $bannedMinutes);
        $this->middleware->handle($request, fn () => new Response('ok'), $maxAttempts, $decayMinutes, $bannedMinutes);
        $this->middleware->handle($request, fn () => new Response('ok'), $maxAttempts, $decayMinutes, $bannedMinutes);

        Carbon::setTestNow(Carbon::now()->addMinutes(5));

        $response = $this->middleware->handle($request, fn () => new Response('ok'), $maxAttempts, $decayMinutes, $bannedMinutes);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRateLimitResetsAfterBanPeriodEndsEvenWhenDecayIsLonger(): void
    {
        Config::set('superban.ban_criteria', 'ip');

        $request = $this->requestWithUser();
        $maxAttempts = 2;
        $decayMinutes = 10;
        $bannedMinutes = 1;

        $this->middleware->handle($request, fn () => new Response('ok'), $maxAttempts, $decayMinutes, $bannedMinutes);
        $this->middleware->handle($request, fn () => new Response('ok'), $maxAttempts, $decayMinutes, $bannedMinutes);
        $this->middleware->handle($request, fn () => new Response('ok'), $maxAttempts, $decayMinutes, $bannedMinutes);

        Carbon::setTestNow(Carbon::now()->addMinutes(2));

        $response = $this->middleware->handle($request, fn () => new Response('ok'), $maxAttempts, $decayMinutes, $bannedMinutes);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testInvalidBanCriteriaThrowsException(): void
    {
        Config::set('superban.ban_criteria', 'invalid');

        $request = $this->requestWithUser();

        $this->expectException(InvalidBanCriteriaException::class);

        $this->middleware->handle($request, fn () => new Response('ok'), 2, 1, 5);
    }
}
