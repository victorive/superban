<?php

declare(strict_types=1);

use Illuminate\Support\Env;

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Superban
    |--------------------------------------------------------------------------
    |
    | You can disable all enforcement globally while keeping the package
    | registered. When disabled, requests pass through untouched.
    |
    */
    'enabled' => Env::get('SUPERBAN_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | The cache store used for both rate-limit counters and ban records.
    | Leave null to use the default cache store. Redis is recommended for
    | production multi-server deployments.
    |
    */
    'store' => Env::get('SUPERBAN_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Key Prefix
    |--------------------------------------------------------------------------
    */
    'prefix' => Env::get('SUPERBAN_PREFIX', 'superban'),

    /*
    |--------------------------------------------------------------------------
    | Identity Criteria
    |--------------------------------------------------------------------------
    |
    | The default criterion used to identify clients. Supported: ip, user_id,
    | email, api_key, or an array of criteria for composite identity.
    |
    */
    'criteria' => Env::get('SUPERBAN_CRITERIA', 'ip'),

    /*
    |--------------------------------------------------------------------------
    | Ban Scope
    |--------------------------------------------------------------------------
    |
    | Controls how widely a ban applies. "global" shares bans across all
    | protected routes; "route" limits the ban to the current route;
    | "route_name" uses the route name; "custom" delegates to a callback.
    |
    */
    'scope' => Env::get('SUPERBAN_SCOPE', 'global'),

    /*
    |--------------------------------------------------------------------------
    | Named Limits
    |--------------------------------------------------------------------------
    |
    | Predefined limit configurations that can be referenced by name in routes:
    | Route::middleware('superban:api')->group(...)
    |
    */
    'limits' => [
        'default' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
            'ban_minutes' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Escalation (Progressive Bans)
    |--------------------------------------------------------------------------
    |
    | When enabled, each subsequent ban within the reset window uses the next
    | value in the ladder. After the ladder is exhausted, max_minutes is used.
    |
    */
    'escalation' => [
        'enabled' => false,
        'ladder' => [5, 60, 1440],
        'reset_after_minutes' => 1440,
        'max_minutes' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowlist
    |--------------------------------------------------------------------------
    |
    | Clients matching these rules are never banned. IP values may be plain
    | addresses or CIDR ranges (e.g. "10.0.0.0/8").
    |
    */
    'allowlist' => [
        'enabled' => true,
        'ips' => [],
        'users' => [],
        'emails' => [],
        'api_keys' => [],
        'routes' => [],
        'environments' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Denylist
    |--------------------------------------------------------------------------
    |
    | Clients matching these rules are banned immediately and permanently.
    |
    */
    'denylist' => [
        'enabled' => true,
        'ips' => [],
        'users' => [],
        'emails' => [],
        'api_keys' => [],
        'routes' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    |
    | format: json | problem+json | html
    |
    */
    'response' => [
        'status' => 429,
        'format' => 'json',
        'headers' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit / Database Store
    |--------------------------------------------------------------------------
    |
    | When enabled, ban records are also persisted to the database. This is
    | required for the list command and long-term audit trails.
    |
    */
    'audit' => [
        'enabled' => false,
        'connection' => null,
        'table' => 'superban_bans',
        'retention_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */
    'events' => true,

    /*
    |--------------------------------------------------------------------------
    | Shadow Mode
    |--------------------------------------------------------------------------
    |
    | In shadow mode, would-be bans are logged and events are fired but the
    | client is never actually blocked. Useful for tuning thresholds.
    |
    */
    'shadow' => false,
];
