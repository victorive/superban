<?php

namespace Victorive\Superban\Tests;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use JsonException;
use Orchestra\Testbench\TestCase;
use Psr\SimpleCache\InvalidArgumentException;
use stdClass;
use Symfony\Component\HttpFoundation\Response;
use Victorive\Superban\Exceptions\InvalidBanCriteriaException;
use Victorive\Superban\Middleware\SuperbanMiddleware;

class SuperbanMiddlewareTest extends TestCase
{
    protected SuperbanMiddleware $superBanMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superBanMiddleware = new SuperbanMiddleware();
    }

    public static function banCriteriaDataProvider(): array
    {
        return [
            ['user_id'],
            ['email'],
            ['ip'],
        ];
    }

    /**
     * @dataProvider banCriteriaDataProvider
     */
    public function testUserNotBannedIsAllowedThrough(string $banCriteria): void
    {
        Config::set('superban.ban_criteria', $banCriteria);

        $user = new stdClass();
        $user->id = 1;
        $user->email = 'test@example.com';

        $request = Request::create('/thisroute');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $maxAttempts = 200;
        $decayMinutes = 2;
        $bannedMinutes = 1440;

        $response = $this->superBanMiddleware->handle($request, function () {
            return new Response('Test response');
        }, $maxAttempts, $decayMinutes, $bannedMinutes);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBannedUserIsDeniedAccess(): void
    {
        Config::set('superban.ban_criteria', 'ip');

        $request = Request::create('/thisroute');
        $middleware = $this->getMockBuilder(SuperbanMiddleware::class)
            ->onlyMethods(['getBannedUntil'])
            ->getMock();

        $middleware->expects($this->once())
            ->method('getBannedUntil')
            ->willReturn(Carbon::now()->addHour()->timestamp);

        $maxAttempts = 200;
        $decayMinutes = 2;
        $bannedMinutes = 1440;

        $response = $middleware->handle($request, function () {
        }, $maxAttempts, $decayMinutes, $bannedMinutes);

        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function testUserExceedingRateLimitIsBanned(): void
    {
        Config::set('superban.ban_criteria', 'ip');

        $request = Request::create('/anotherroute');

        $maxAttempts = 200;
        $decayMinutes = 2;
        $bannedMinutes = 1440;

        for ($i = 0; $i < 201; $i++) {
            $this->superBanMiddleware->handle($request, function () {
                return new Response('Test response');
            }, $maxAttempts, $decayMinutes, $bannedMinutes);
        }

        $response = $this->superBanMiddleware->handle($request, function () {
        }, $maxAttempts, $decayMinutes, $bannedMinutes);

        $this->assertEquals(429, $response->getStatusCode());

        $expectedMessage = 'You have been banned until ' . Carbon::now()->addMinutes($bannedMinutes)->toDateTimeString();
        $actualJson = $response->getContent();
        $expectedJson = json_encode(['message' => $expectedMessage], JSON_THROW_ON_ERROR);
        $this->assertJsonStringEqualsJsonString($expectedJson, $actualJson);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testInvalidBanCriteriaThrowsException(): void
    {
        config(['superban.ban_criteria' => 'invalid']);

        $maxAttempts = 200;
        $decayMinutes = 2;
        $bannedMinutes = 1440;

        $request = Request::create('/thisroute');

        $this->expectException(InvalidBanCriteriaException::class);

        $this->superBanMiddleware->handle($request, function () {
        }, $maxAttempts, $decayMinutes, $bannedMinutes);
    }
}
