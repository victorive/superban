# Superban

Superban is a Laravel package designed to enable you ban API clients for a specified period. 
It allows you to easily limit the number of requests a client can execute within a certain time frame,
and if they surpass this limit, they will be banned for the specified duration.

## Installation

Install the package via composer using:

```bash
composer require victorive/superban
```

Next, publish the configuration file `(config/superban.php)` with:

```bash
php artisan vendor:publish --tag="superban-config"
```

The published configuration file enables you to customize the `SUPERBAN_CACHE_DRIVER` and `SUPERBAN_BAN_CRITERIA` parameters 
for rate-limiting operations. These settings can be modified in your .env file with your preferred values.

## Configuration
* The `SUPERBAN_CACHE_DRIVER` parameter determines the cache driver used for Superban operations. 
Supported drivers include `"array", "database", "file", "memcached", "redis", "dynamodb", and "octane".`
* The `SUPERBAN_BAN_CRITERIA` parameter sets the criteria for rate-limiting or banning users. 
* Supported options include `"user_id", "email", and "ip"`.

Example configuration:

```php
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
```

Update your `.env` file with your preferred values for the keys below:
>
> SUPERBAN_CACHE_DRIVER= {{your preferred cache driver}}
>
> SUPERBAN_BAN_CRITERIA= {{your preferred ban criteria}}

## Usage
To utilize Superban's functionalities, add the following to your `app/Http/Kernel.php` file.

```php
protected $middlewareAliases = [
    // ...
    'superban' => \Victorive\Superban\Middleware\SuperbanMiddleware::class,
];
```

Then you can protect your routes using the middleware rules. For instance

* **Route group:**

```php
Route::middleware(['superban:100,2,720'])->group(function () {
   Route::post('/someroute', function () {
       // ...
   });
 
   Route::post('anotherroute', function () {
       // ...
   });
});
```

* **Single route:**

```php
Route::post('/thisroute', function () {
    // ...
})->middleware(['superban:100,2,720']);
```

In the examples above, **100** is the maximum number of requests allowed, **2** is the time period (in minutes)
during which these requests can occur, and **720** is the duration (in minutes) for which the user will be banned
after exceeding the limit.

## Testing

To run the tests, use the following command:

```bash
/vendor/bin/phpunit
```

## Changelog

For information on recent updates, refer to the [CHANGELOG](CHANGELOG.md) file.

## Contributing

Contributions are welcome!

## License

This package is licensed under the MIT License. For more information, please see the [License File](LICENSE.md).

