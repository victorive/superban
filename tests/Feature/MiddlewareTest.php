<?php

declare(strict_types=1);

namespace Victorive\Superban\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Victorive\Superban\Tests\TestCase;

final class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_allowed_requests_pass_through(): void
    {
        Route::middleware(['superban:2,1,5'])->get('/protected', fn () => 'ok');

        $this->get('/protected')->assertOk();
        $this->get('/protected')->assertOk();
    }

    public function test_exceeding_limit_returns_ban_response(): void
    {
        Route::middleware(['superban:2,1,5'])->get('/protected', fn () => 'ok');

        $this->get('/protected')->assertOk();
        $this->get('/protected')->assertOk();

        $response = $this->get('/protected');
        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-Ban-Until');
        $response->assertJsonPath('message', fn (string $message) => str_contains($message, 'banned until'));
    }

    public function test_ban_expires_after_duration(): void
    {
        Route::middleware(['superban:2,1,1'])->get('/protected', fn () => 'ok');

        $this->get('/protected')->assertOk();
        $this->get('/protected')->assertOk();
        $this->get('/protected')->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);

        Carbon::setTestNow(Carbon::now()->addMinutes(2));

        $this->get('/protected')->assertOk();
    }

    public function test_guest_with_user_id_criteria_falls_back_to_ip(): void
    {
        Config::set('superban.criteria', 'user_id');
        Route::middleware(['superban:2,1,5'])->get('/protected', fn () => 'ok');

        $this->get('/protected')->assertOk();
    }

    public function test_composite_identity_allowlist_matches_any_part(): void
    {
        Config::set('superban.criteria', 'ip,user_id');
        Config::set('superban.allowlist.ips', ['127.0.0.1']);
        Route::middleware(['superban:1,1,5'])->get('/protected', fn () => 'ok');

        $this->get('/protected')->assertOk();
        $this->get('/protected')->assertOk();
    }

    public function test_named_limit_is_resolved(): void
    {
        Config::set('superban.limits.api', [
            'max_attempts' => 1,
            'decay_minutes' => 1,
            'ban_minutes' => 5,
        ]);

        Route::middleware(['superban:api'])->get('/api', fn () => 'ok');

        $this->get('/api')->assertOk();
        $this->get('/api')->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }

    public function test_allowlisted_requests_are_never_banned(): void
    {
        Config::set('superban.allowlist.ips', ['127.0.0.1']);
        Route::middleware(['superban:1,1,5'])->get('/protected', fn () => 'ok');

        $this->get('/protected')->assertOk();
        $this->get('/protected')->assertOk();
    }

    public function test_denylisted_requests_are_banned_immediately(): void
    {
        Config::set('superban.denylist.ips', ['127.0.0.1']);
        Route::middleware(['superban:100,1,5'])->get('/protected', fn () => 'ok');

        $this->get('/protected')->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
