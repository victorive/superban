<?php

declare(strict_types=1);

namespace Victorive\Superban\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Victorive\Superban\Criteria\IpResolver;
use Victorive\Superban\DTO\Identity;
use Victorive\Superban\DTO\Limit;
use Victorive\Superban\Facades\Superban;
use Victorive\Superban\Tests\TestCase;

final class ManagerTest extends TestCase
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

    public function test_manual_ban_and_unban(): void
    {
        $identity = new Identity('user_id', '42', '42');

        $ban = Superban::ban($identity, 10, 'abuse');

        $this->assertTrue(Superban::isBanned($identity));
        $this->assertSame('abuse', Superban::status($identity)->reason);

        Superban::unban($identity);

        $this->assertFalse(Superban::isBanned($identity));
    }

    public function test_escalation_increases_ban_duration(): void
    {
        Config::set('superban.escalation.enabled', true);
        Config::set('superban.escalation.ladder', [5, 10, 15]);
        Config::set('superban.escalation.reset_after_minutes', 1440);
        Config::set('superban.escalation.max_minutes', 100);

        $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '10.0.0.1']);
        $identity = (new IpResolver)->resolve($request);
        $limit = new Limit('test', 1, 1, 5);

        Superban::enforce($request, $limit);
        Superban::enforce($request, $limit);
        $ban1 = Superban::status($identity);
        $this->assertNotNull($ban1);
        $this->assertSame(1, $ban1->strikes);

        Carbon::setTestNow(Carbon::now()->addMinutes(6));
        Superban::enforce($request, $limit);
        Superban::enforce($request, $limit);
        $ban2 = Superban::status($identity);
        $this->assertNotNull($ban2);
        $this->assertSame(2, $ban2->strikes);
        $this->assertGreaterThan($ban1->bannedUntil, $ban2->bannedUntil);
    }

    public function test_fake_helper_works(): void
    {
        $fake = Superban::fake();

        $identity = new Identity('user_id', '99', '99');
        Superban::ban($identity, 5);

        $fake->assertBanned($identity);
    }
}
