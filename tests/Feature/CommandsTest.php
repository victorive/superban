<?php

declare(strict_types=1);

namespace Victorive\Superban\Tests\Feature;

use Victorive\Superban\DTO\Identity;
use Victorive\Superban\Facades\Superban;
use Victorive\Superban\Tests\TestCase;

final class CommandsTest extends TestCase
{
    public function test_ban_command_creates_a_ban(): void
    {
        $this->artisan('superban:ban', [
            'identity' => 'user:42',
            '--minutes' => '10',
            '--reason' => 'manual',
        ])->assertSuccessful();

        $this->assertTrue(Superban::isBanned(new Identity('user_id', '42', '42')));
    }

    public function test_unban_command_removes_a_ban(): void
    {
        Superban::ban(new Identity('user_id', '42', '42'), 10);

        $this->artisan('superban:unban', [
            'identity' => 'user:42',
        ])->assertSuccessful();

        $this->assertFalse(Superban::isBanned(new Identity('user_id', '42', '42')));
    }

    public function test_status_command_reports_active_ban(): void
    {
        Superban::ban(new Identity('user_id', '42', '42'), 10);

        $this->artisan('superban:status', [
            'identity' => 'user:42',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Banned until');
    }
}
