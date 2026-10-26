# Upgrade Guide

## From 1.x to 2.0

Superban 2.0 is a major rewrite targeting Laravel 12+ and PHP 8.3+. Review the changes below before upgrading.

### Requirements

- PHP `^8.3`
- Laravel `^12.0 || ^13.0`

If your application runs on Laravel 10 or 11, stay on the `^1.0` line.

### Configuration

Republish the config file after upgrading:

```bash
php artisan vendor:publish --tag="superban-config" --force
```

Replace the old environment variables with the new ones:

| Old | New |
| --- | --- |
| `SUPERBAN_CACHE_DRIVER` | `SUPERBAN_STORE` |
| `SUPERBAN_BAN_CRITERIA` | `SUPERBAN_CRITERIA` |

The config file now also contains `scope`, `limits`, `escalation`, `allowlist`, `denylist`, `response`, `audit`, `events`, `fail_open`, and `shadow` sections. Update these to match your application's needs.

### Middleware

The legacy inline syntax continues to work:

```php
Route::middleware(['superban:100,2,720'])->group(function () {
    // ...
});
```

You may now also use named limits:

```php
Route::middleware(['superban:api'])->group(function () {
    // uses config('superban.limits.api')
});
```

The middleware is now registered automatically via the service provider. You no longer need to add it manually to `app/Http/Kernel.php`.

### Identity criteria

Supported criteria remain `ip`, `user_id`, `email`, and `api_key`. Composite criteria such as `ip,user_id` are now supported. Guest requests automatically fall back to `ip` when a user-based criterion cannot be resolved.

### Response format

The response format is now configurable. The default is JSON. To return HTML or Problem Details responses, set:

```php
'superban.response.format' => 'html',        // or 'problem+json'
```

### Manual bans

If you issued manual bans programmatically, update to the new `Identity` DTO:

```php
use Victorive\Superban\DTO\Identity;
use Victorive\Superban\Facades\Superban;

Superban::ban(new Identity('user_id', '42'), 60, 'abuse');
```

### Events

Events are enabled by default. You can disable them via config:

```php
'superban.events' => false,
```

Event class names changed to:

- `Victorive\Superban\Events\LimitExceeded`
- `Victorive\Superban\Events\ClientBanned`
- `Victorive\Superban\Events\ClientUnbanned`
- `Victorive\Superban\Events\BanExpired`

### Testing

Replace any direct middleware tests with the `Superban::fake()` helper:

```php
use Victorive\Superban\DTO\Identity;
use Victorive\Superban\Facades\Superban;

$fake = Superban::fake();

Superban::ban(new Identity('user_id', '1'), 10);

$fake->assertBanned(new Identity('user_id', '1'));
```
