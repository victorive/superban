<?php

declare(strict_types=1);

namespace Victorive\Superban\Tests\Unit;

use Victorive\Superban\DTO\Identity;
use Victorive\Superban\Tests\TestCase;

final class IdentityTest extends TestCase
{
    public function test_key_generation_is_deterministic(): void
    {
        $identity = new Identity('ip', '127.0.0.1');
        $key1 = $identity->toKey('superban', 'global');
        $key2 = $identity->toKey('superban', 'global');

        $this->assertSame($key1, $key2);
        $this->assertStringStartsWith('superban:global:ip:', $key1);
    }

    public function test_different_values_produce_different_keys(): void
    {
        $a = new Identity('ip', '127.0.0.1');
        $b = new Identity('ip', '127.0.0.2');

        $this->assertNotSame($a->toKey('superban', 'global'), $b->toKey('superban', 'global'));
    }
}
