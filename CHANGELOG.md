# Changelog

All notable changes to `superban` will be documented in this file.

## 2.0.0 - 2026-10-26

### Added

- Full rewrite around contracts: `BanStore`, `Counter`, `BanIdentityResolver`, `BanResponder`, and `MetricsRecorder`.
- Cache-based default ban store with separate counter keys (`:counter` suffix) and hashed namespaced ban keys.
- Optional database audit store with migration, `superban:list`, and `superban:prune` commands.
- Named limits configured in `config/superban.php` in addition to legacy inline middleware syntax.
- Composite identity criteria (e.g. `ip,user_id`).
- Allow/deny lists with IP CIDR, user, email, api_key, route, and environment matching.
- Progressive escalation ladder with configurable reset window and maximum duration.
- Shadow mode for safe threshold tuning.
- Events: `LimitExceeded`, `ClientBanned`, `ClientUnbanned`, `BanExpired`.
- `Superban::fake()` testing helper with assertion methods.
- Multiple response formats: `json`, `problem+json`, and `html`.
- Translatable response messages.

### Changed

- Minimum PHP version raised to `8.3`.
- Minimum Laravel version raised to `12.0`.
- Configuration file reshaped; legacy `SUPERBAN_CACHE_DRIVER` and `SUPERBAN_BAN_CRITERIA` keys replaced by `SUPERBAN_STORE`, `SUPERBAN_CRITERIA`, `SUPERBAN_SCOPE`, limits, escalation, allow/deny lists, audit, and response sections.

### Removed

- Direct middleware constructor instantiation support (middleware is resolved via the container).

## 1.0.2 - 2026-10-26

### Fixed

- Guest request crashes when using non-IP criteria.
- Key collisions between ban records and rate-limit counters.
- Missing config merge in service provider.
- Missing `superban` middleware alias registration.
- Counter not clearing after a ban is issued.
- Flaky tests caused by shared cache state and timing.

## 1.0.1 - 2023-12-22

- Bug fix.

## 1.0.0 - 2023-12-22

- Initial release.
