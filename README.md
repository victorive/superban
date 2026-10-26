# Superban

[![Latest Version on Packagist](https://img.shields.io/packagist/v/victorive/superban.svg?style=flat-square)](https://packagist.org/packages/victorive/superban)
[![Tests](https://img.shields.io/github/actions/workflow/status/victorive/superban/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/victorive/superban/actions/workflows/run-tests.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/victorive/superban/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/victorive/superban/actions/workflows/phpstan.yml)

Superban is a Laravel package that bans API clients for a specified period after they exceed a rate limit. It goes beyond Laravel's built-in rate limiter by keeping persistent ban records, supporting progressive escalation, and providing operational commands and events.

## Requirements

- PHP `^8.3`
- Laravel `^12.0 || ^13.0`

For Laravel 10 / 11 projects, use the `^1.0` line (latest `1.0.2`).

## Installation

```bash
composer require victorive/superban
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="superban-config"
```

Publish translations (optional):

```bash
php artisan vendor:publish --tag="superban-lang"
```

Publish database migrations if you want audit persistence (required for `superban:list` and `superban:prune`):

```bash
php artisan vendor:publish --tag="superban-migrations"
php artisan migrate
```

## Configuration

Key environment variables:

| Variable | Default | Description |
| --- | --- | --- |
| `SUPERBAN_ENABLED` | `true` | Globally enable or disable enforcement. |
| `SUPERBAN_STORE` | `null` | Cache store name; `null` uses the default cache store. Redis is recommended for production. |
| `SUPERBAN_PREFIX` | `superban` | Prefix for cache/database keys. |
| `SUPERBAN_CRITERIA` | `ip` | Identity criterion: `ip`, `user_id`, `email`, `api_key`, or a comma-separated composite such as `ip,user_id`. |
| `SUPERBAN_SCOPE` | `global` | How bans are scoped: `global`, `route`, or `route_name`. |

See `config/superban.php` for allow/deny lists, escalation, response formatting, audit, shadow mode, and named limits.

## Usage

### Middleware syntax

The legacy inline syntax is still supported:

```php
Route::middleware(['superban:100,2,720'])->group(function () {
    // 100 max attempts, 2 minute decay window, 720 minute ban
});
```

You can also reference a named limit from the config:

```php
Route::middleware(['superban:api'])->group(function () {
    // uses config('superban.limits.api')
});
```

### Identity criteria

```php
'superban' => [
    'criteria' => 'ip',        // ban by IP address
    'criteria' => 'user_id',   // ban by authenticated user id (falls back to ip for guests)
    'criteria' => 'email',     // ban by authenticated user email (falls back to ip for guests)
    'criteria' => 'api_key',   // ban by request header / query api_key
    'criteria' => 'ip,user_id',// composite identity requiring both
],
```

### Allow and deny lists

```php
'superban' => [
    'allowlist' => [
        'enabled' => true,
        'ips' => ['127.0.0.1', '10.0.0.0/8'],
        'users' => ['1', '2'],
        'emails' => ['admin@example.com'],
        'routes' => ['health'],
    ],
    'denylist' => [
        'enabled' => true,
        'ips' => ['192.0.2.0/24'],
        'users' => ['99'],
    ],
],
```

### Progressive escalation

```php
'superban' => [
    'escalation' => [
        'enabled' => true,
        'ladder' => [5, 60, 1440], // minutes for 1st, 2nd, 3rd strike
        'reset_after_minutes' => 1440,
        'max_minutes' => 10080,
    ],
],
```

### Facade

```php
use Victorive\Superban\DTO\Identity;
use Victorive\Superban\Facades\Superban;

// Manual ban
Superban::ban(new Identity('user_id', '42'), 60, 'abuse');

// Check / lift
if (Superban::isBanned(new Identity('user_id', '42'))) {
    Superban::unban(new Identity('user_id', '42'));
}
```

### Artisan commands

```bash
php artisan superban:ban user:42 --minutes=60 --reason=abuse
php artisan superban:unban user:42
php artisan superban:status user:42
php artisan superban:list
php artisan superban:prune --days=90
```

### Events

Subscribe to these events in your application:

- `Victorive\Superban\Events\LimitExceeded`
- `Victorive\Superban\Events\ClientBanned`
- `Victorive\Superban\Events\ClientUnbanned`
- `Victorive\Superban\Events\BanExpired`

Events can be disabled via `superban.events`.

### Testing

Use the fake helper to write deterministic tests:

```php
use Victorive\Superban\DTO\Identity;
use Victorive\Superban\Facades\Superban;

public function test_banned_users_are_blocked(): void
{
    $fake = Superban::fake();

    Superban::ban(new Identity('user_id', '1'), 10);

    $fake->assertBanned(new Identity('user_id', '1'));
}
```

## Testing the package

```bash
composer check
```

This runs Pint, PHPStan, and PHPUnit. Individual commands:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
vendor/bin/phpunit
```

## Upgrading

See [UPGRADE.md](UPGRADE.md) for migration notes from 1.x to 2.0.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

Contributions are welcome! Please open an issue or pull request.

## License

The MIT License. See [LICENSE.md](LICENSE.md).
