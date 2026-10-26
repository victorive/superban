<?php

declare(strict_types=1);

namespace Victorive\Superban;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\IpUtils;
use Victorive\Superban\Contracts\BanIdentityResolver;
use Victorive\Superban\Contracts\BanResponder;
use Victorive\Superban\Contracts\BanStore;
use Victorive\Superban\Contracts\Counter;
use Victorive\Superban\Contracts\MetricsRecorder;
use Victorive\Superban\DTO\Ban;
use Victorive\Superban\DTO\Decision;
use Victorive\Superban\DTO\Identity;
use Victorive\Superban\DTO\Limit;
use Victorive\Superban\Events\BanExpired;
use Victorive\Superban\Events\ClientBanned;
use Victorive\Superban\Events\ClientUnbanned;
use Victorive\Superban\Events\LimitExceeded;
use Victorive\Superban\Exceptions\InvalidConfigurationException;
use Victorive\Superban\Testing\SuperbanFake;

final class SuperbanManager
{
    /**
     * @var array<string, Limit>
     */
    private array $limits = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private array $config,
        private BanIdentityResolver $resolver,
        private BanStore $store,
        private Counter $counter,
        private BanResponder $responder,
        private Dispatcher $events,
        private MetricsRecorder $metrics,
        private Repository $cache,
    ) {}

    public function limit(string $name, Limit $limit): self
    {
        $this->limits[$name] = $limit;

        return $this;
    }

    /**
     * @param  string|array<int, string>  $parameters
     */
    public function resolveLimit(string|array $parameters): Limit
    {
        $parameters = is_array($parameters) ? $parameters : explode(',', $parameters);

        if (isset($parameters[0]) && is_numeric($parameters[0])) {
            return new Limit(
                'inline',
                (int) $parameters[0],
                (int) ($parameters[1] ?? 1),
                (int) ($parameters[2] ?? 60),
            );
        }

        $name = $parameters[0] ?? 'default';

        if (isset($this->limits[$name])) {
            return $this->limits[$name];
        }

        $configured = $this->config['limits'][$name] ?? null;

        if ($configured !== null) {
            return Limit::fromConfig($name, $configured);
        }

        throw new InvalidConfigurationException("Unknown Superban limit [{$name}].");
    }

    public function enforce(Request $request, Limit $limit): Decision
    {
        if (! ($this->config['enabled'] ?? true)) {
            return Decision::allowed();
        }

        $identity = $this->resolver->resolve($request);
        $scope = $this->scope($request);
        $key = $identity->toKey($this->config['prefix'], $scope);

        if ($this->allowlisted($request, $identity)) {
            return Decision::allowed();
        }

        if ($this->denylisted($request, $identity)) {
            $ban = $this->createBan($identity, $key, PHP_INT_MAX, $scope, 'denylist');
            $this->store->put($key, $ban);
            $this->dispatch(new ClientBanned($ban, $limit, $request));

            return Decision::banned($ban, $limit);
        }

        $existing = $this->store->get($key);
        if ($existing !== null && ! $existing->expired()) {
            return Decision::banned($existing, $limit);
        }

        if ($existing !== null && $existing->expired()) {
            $this->store->forget($key);
            $this->dispatch(new BanExpired($identity, (int) Carbon::now()->timestamp));
        }

        if ($this->config['shadow'] ?? false) {
            $counterKey = $key.':counter';
            $attempts = $this->counter->hit($counterKey, $limit->decayMinutes * 60);

            if ($attempts > $limit->maxAttempts) {
                $this->dispatch(new LimitExceeded($identity, $limit, $request));
                $this->metrics->record('superban.shadow_ban', $this->metricTags($identity, $scope));
            }

            return Decision::allowed(max(0, $limit->maxAttempts - $attempts));
        }

        $counterKey = $key.':counter';
        $attempts = $this->counter->hit($counterKey, $limit->decayMinutes * 60);
        $remaining = max(0, $limit->maxAttempts - $attempts);

        if ($attempts > $limit->maxAttempts) {
            $this->dispatch(new LimitExceeded($identity, $limit, $request));

            $ban = $this->createBan($identity, $key, $limit->banMinutes, $scope, 'rate_limit');
            $this->store->put($key, $ban);
            $this->counter->clear($counterKey);

            $this->dispatch(new ClientBanned($ban, $limit, $request));
            $this->metrics->record('superban.banned', $this->metricTags($identity, $scope));

            return Decision::banned($ban, $limit);
        }

        $this->metrics->record('superban.allowed', $this->metricTags($identity, $scope));

        return Decision::allowed($remaining);
    }

    public function ban(Identity $identity, int $minutes, ?string $reason = null, ?string $scope = null): Ban
    {
        $scope ??= 'global';
        $key = $identity->toKey($this->config['prefix'], $scope);
        $ban = $this->createBan($identity, $key, $minutes, $scope, $reason ?? 'manual');
        $this->store->put($key, $ban);
        $this->dispatch(new ClientBanned($ban, null, null));
        $this->metrics->record('superban.banned', $this->metricTags($identity, $scope));

        return $ban;
    }

    public function unban(Identity $identity, ?string $scope = null): void
    {
        $scope ??= 'global';
        $key = $identity->toKey($this->config['prefix'], $scope);
        $this->store->forget($key);
        $this->dispatch(new ClientUnbanned($identity, 'manual'));
        $this->metrics->record('superban.unbanned', $this->metricTags($identity, $scope));
    }

    public function isBanned(Identity $identity, ?string $scope = null): bool
    {
        return $this->status($identity, $scope) !== null;
    }

    public function status(Identity $identity, ?string $scope = null): ?Ban
    {
        $scope ??= 'global';
        $key = $identity->toKey($this->config['prefix'], $scope);
        $ban = $this->store->get($key);

        if ($ban !== null && $ban->expired()) {
            $this->store->forget($key);
            $this->dispatch(new BanExpired($identity, (int) Carbon::now()->timestamp));

            return null;
        }

        return $ban;
    }

    public function resolver(): BanIdentityResolver
    {
        return $this->resolver;
    }

    public function store(): BanStore
    {
        return $this->store;
    }

    public function responder(): BanResponder
    {
        return $this->responder;
    }

    public static function fake(): SuperbanFake
    {
        $fake = new SuperbanFake;

        app()->instance(BanStore::class, $fake);
        app()->instance(Counter::class, $fake);
        app()->forgetInstance(SuperbanManager::class);
        Facade::clearResolvedInstance(SuperbanManager::class);

        return $fake;
    }

    public function scope(Request $request): string
    {
        return match ($this->config['scope'] ?? 'global') {
            'global' => 'global',
            'route' => $request->route()?->uri() ?? 'unknown',
            'route_name' => $request->route()?->getName() ?? 'unnamed',
            default => 'global',
        };
    }

    private function createBan(Identity $identity, string $key, int $baseMinutes, string $scope, string $reason): Ban
    {
        $minutes = $this->escalatedBanMinutes($identity, $scope, $baseMinutes);
        $strikes = $this->nextStrike($identity, $scope);
        $now = Carbon::now();

        return new Ban(
            $key,
            $identity,
            (int) $now->copy()->addMinutes($minutes)->timestamp,
            (int) $now->timestamp,
            $strikes,
            $reason,
            $scope,
        );
    }

    private function escalatedBanMinutes(Identity $identity, string $scope, int $baseMinutes): int
    {
        $escalation = $this->config['escalation'] ?? ['enabled' => false];

        if (! ($escalation['enabled'] ?? false)) {
            return $baseMinutes;
        }

        $strikes = $this->currentStrikes($identity, $scope);
        $ladder = $escalation['ladder'] ?? [5, 60, 1440];
        $index = max(0, $strikes - 1);

        $minutes = $ladder[$index] ?? end($ladder);

        return min((int) $minutes, (int) ($escalation['max_minutes'] ?? PHP_INT_MAX));
    }

    private function nextStrike(Identity $identity, string $scope): int
    {
        $key = $this->strikesKey($identity, $scope);
        $ttl = (int) ($this->config['escalation']['reset_after_minutes'] ?? 1440) * 60;

        $this->cache->add($key, 0, $ttl);

        $value = $this->cache->increment($key);

        return $value === false ? 1 : (int) $value;
    }

    private function currentStrikes(Identity $identity, string $scope): int
    {
        return (int) $this->cache->get($this->strikesKey($identity, $scope), 0);
    }

    private function strikesKey(Identity $identity, string $scope): string
    {
        return "{$this->config['prefix']}:strikes:{$scope}:{$identity->type}:".hash('sha256', $identity->value);
    }

    private function allowlisted(Request $request, Identity $identity): bool
    {
        $allowlist = $this->config['allowlist'] ?? ['enabled' => false];

        if (! ($allowlist['enabled'] ?? false)) {
            return false;
        }

        $ip = $request->ip();

        if (in_array(app()->environment(), $allowlist['environments'] ?? [], true)) {
            return true;
        }

        if ($ip !== null && $this->matchesIp($ip, $allowlist['ips'] ?? [])) {
            return true;
        }

        if ($this->identityInList($identity, 'user_id', $allowlist['users'] ?? [])) {
            return true;
        }

        if ($this->identityInList($identity, 'email', $allowlist['emails'] ?? [])) {
            return true;
        }

        if ($this->identityInList($identity, 'api_key', $allowlist['api_keys'] ?? [])) {
            return true;
        }

        $route = $request->route();
        if ($route !== null && in_array($route->getName(), $allowlist['routes'] ?? [], true)) {
            return true;
        }

        return false;
    }

    private function denylisted(Request $request, Identity $identity): bool
    {
        $denylist = $this->config['denylist'] ?? ['enabled' => false];

        if (! ($denylist['enabled'] ?? false)) {
            return false;
        }

        $ip = $request->ip();

        if ($ip !== null && $this->matchesIp($ip, $denylist['ips'] ?? [])) {
            return true;
        }

        if ($this->identityInList($identity, 'user_id', $denylist['users'] ?? [])) {
            return true;
        }

        if ($this->identityInList($identity, 'email', $denylist['emails'] ?? [])) {
            return true;
        }

        if ($this->identityInList($identity, 'api_key', $denylist['api_keys'] ?? [])) {
            return true;
        }

        $route = $request->route();
        if ($route !== null && in_array($route->getName(), $denylist['routes'] ?? [], true)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, string>  $rules
     */
    private function matchesIp(?string $ip, array $rules): bool
    {
        if ($ip === null) {
            return false;
        }

        foreach ($rules as $rule) {
            if (IpUtils::checkIp($ip, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $list
     */
    private function identityInList(Identity $identity, string $type, array $list): bool
    {
        if ($identity->type === 'composite') {
            foreach (explode('|', $identity->value) as $part) {
                $segment = explode(':', $part, 2) + [null, null];

                if ($segment[0] === $type && in_array((string) $segment[1], $list, true)) {
                    return true;
                }
            }

            return false;
        }

        if ($identity->type !== $type) {
            return false;
        }

        return in_array($identity->value, $list, true);
    }

    private function dispatch(object $event): void
    {
        if ($this->config['events'] ?? true) {
            $this->events->dispatch($event);
        }
    }

    /**
     * @return array<string, string>
     */
    private function metricTags(Identity $identity, string $scope): array
    {
        return [
            'type' => $identity->type,
            'scope' => $scope,
        ];
    }
}
