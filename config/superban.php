<?php

return [
    /**
     * The cache driver to use for superban operations.
     *
     * Supported drivers: "array", "database", "file",
     * "memcached", "redis", "dynamodb", "octane"
     */
    'cache_driver' => env('SUPERBAN_CACHE_DRIVER', 'file'),

    /**
     * The ban criteria to use when rate-limiting/banning users.
     *
     * Supported options: "user_id", "email", "ip",
     */
    'ban_criteria' => env('SUPERBAN_BAN_CRITERIA', 'ip'),
];
