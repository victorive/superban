# Changelog

All notable changes to `superban` will be documented in this file.\

## 1.0.2 - 2026-10-26

- Fix guest requests crashing when `user_id` or `email` ban criteria are used.
- Register the `superban` middleware alias automatically.
- Merge config so the package works without publishing the config file.
- Prefix cache/rate-limiter keys to avoid collisions.
- Clear rate-limiter attempts when a ban starts.
- Add `Retry-After` header to ban responses.
- Improve test reliability and coverage for guest/edge cases.

## 1.0.1 - 2024-01-16

- bug fix

## 1.0.1 - 2023-12-22

- bug fix

## 1.0.0 - 2023-12-22

- initial release
