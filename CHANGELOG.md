# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.3.0] - 2026-05-23

### Added

- **Laravel 12 support** — `illuminate/support` constraint extended to `^12.0`; CI matrix now covers Laravel 12 with `orchestra/testbench ^10.0` on PHP 8.2–8.4.
- **Laravel 11 support** — CI matrix covers Laravel 11 with `orchestra/testbench ^9.0` on PHP 8.2–8.4.
- **GitHub Actions CI** — full test matrix across PHP 8.1–8.4 and Laravel 8–12 (with appropriate exclusions for EOL/incompatible combinations).
- **Test suite** — 105 tests covering `AppMeta`, `ModelMeta`, `HasMetas`, `EnumConfig`, `History`, and category scoping.

### Changed

- `osoobe/laravel-utilities` moved from `require` to `suggest`. All utility calls were trivial and have been inlined, removing the external dependency entirely. If you relied on `getVersionHostnames()` or `appendVersionHostname()` on `AppMeta`, the optional package is still supported — add it to your own `composer.json`.
- `AppMeta::appendVersionHostname` now uses `gethostname()` directly instead of `Utilities::getHostnameHash()`.
- `AppMeta` version resolution now uses `config('app.version', '1.0')` instead of the `version('short')` helper from `laravel-utilities`.

### Fixed

- `MetaTrait::bootMetaTrait` called `self::getDefaultCategory()` instead of `static::getDefaultCategory()`, so category scoping was silently ignored on `AppMeta` subclasses that override `getDefaultCategory()`.
- `History::bootHistory` used `getRawOriginal()` in the `created` listener, which returns an empty array before `syncOriginal` runs. Changed to `getAttributes()` so tracked fields are actually recorded on creation.

### Removed

- `TimeDiff` trait removed from `AppMeta` and `ModelMeta` (it was pulled in from `laravel-utilities` and is unrelated to settings storage).

## [2.0.0] - 2026-05-11

### Added

- `EnumConfig` trait — apply to a PHP backed enum to delegate `config()`, `setConfig()`, `secret()`, and `setSecret()` calls through the enum case's `value` as the meta key.
- `AppMeta::keyExists(string $key): bool` — check whether a meta key exists without fetching its value.
- `AppMeta::setConfig` / `AppMeta::config` — persist a value to the database and optionally sync it into Laravel's runtime config.
- `AppMeta::secret` / `AppMeta::setSecret` — encrypted counterparts to `config` / `setConfig` using Laravel's `encrypt()` / `decrypt()`.
- `HasMetas::scopeHasMetas` — Eloquent query scope to filter models by a related meta key/value pair.
- `History` trait — hooks into Eloquent `created`/`updated` events to append change diffs to a `history` meta entry on models that use `HasMetas`.
- README with full usage documentation and examples.
- CLAUDE.md with codebase architecture guidance.

### Changed

- `composer.json` updated to support Laravel 10 (`illuminate/support ^10.0`) and PHP `^8.1`.
- `meta_value` column is now nullable in migrations.
- `AppMeta::getMeta` and `getOrCreateMeta` now accept a `$default` parameter.

[Unreleased]: https://github.com/osoobe/laravel-settings/compare/v2.3.0...HEAD
[2.3.0]: https://github.com/osoobe/laravel-settings/compare/2.0.0...v2.3.0
[2.0.0]: https://github.com/osoobe/laravel-settings/releases/tag/2.0.0
