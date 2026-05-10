# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`osoobe/laravel-settings` is a Laravel **Composer package** (not a standalone app) that provides two parallel systems for storing key/value metadata:

- **`AppMeta`** — global, application-wide settings in the `app_metas` table.
- **`ModelMeta`** — per-Eloquent-model settings in the `model_metas` table, associated via a polymorphic `morphMany` relation.

## Commands

```bash
# Install dependencies
composer install

# Run all tests
composer test
# or
vendor/bin/phpunit

# Run a single test file
vendor/bin/phpunit tests/SomeTest.php

# Run a single test method
vendor/bin/phpunit --filter testMethodName
```

There is no build step or separate linting command. StyleCI (Laravel preset) is used for style checks via CI.

## Architecture

### Core Trait: `MetaTrait` (`src/Traits/MetaTrait.php`)

Both `AppMeta` and `ModelMeta` use `MetaTrait`, which provides all CRUD and caching logic:

- **`getMeta($key)`** / **`updateMeta($key, $val)`** / **`createMeta($key, $val)`** — basic DB reads/writes.
- **`getOrCreateMeta($key, $default)`** — reads or seeds a value.
- **`getMetaOrCached($key, $default, $type, $clear)`** — reads from `Cache::forever()`, falling back to the DB and auto-creating the record if missing.
- **`config($key, $default)`** — reads from DB first, falls back to Laravel's `config()`.
- **`secret($key)`** / **`setSecret($key, $value)`** — same as `config`/`setConfig` but values are encrypted via `encrypt()`/`decrypt()`.
- **`setConfig($key, $value)`** — persists to DB and also calls `config([$key => $value])` to update the runtime config.

**Type serialization** happens automatically in `bootMetaTrait()` (called by Laravel's boot convention for traits): on `creating` and `updating`, `meta_type` is set to `gettype($meta_value)`, and arrays are JSON-encoded into `meta_value`. Reading back is done in the `getValueAttribute()` accessor, which decodes JSON for arrays and uses `settype()` for scalars.

**Cache key format**: `{prefix}{meta_key}` where prefix is `app_meta_` (AppMeta) or `model_meta_` (ModelMeta). The prefix can be overridden by defining `getKeyPrefix()` on a subclass.

**Category scoping**: If a model class defines `CATEGORY` to anything other than `"default"`, `bootMetaTrait` adds a global Eloquent scope filtering all queries to that category. This allows subclassing `AppMeta` for domain-specific grouped settings.

### `AppMeta` (`src/Models/AppMeta.php`)

Global settings in `app_metas`. Key fields: `meta_key` (unique), `meta_value`, `meta_type`, `category`, `data` (JSON blob). Supports soft deletes. Additional helpers for versioned hostname tracking (`getVersionHostnames`, `appendVersionHostname`) depend on `osoobe/laravel-utilities`.

### `ModelMeta` (`src/Models/ModelMeta.php`)

Per-model settings in `model_metas`. Key difference from `AppMeta`: uniqueness is composite `(model_id, model_type, meta_key)` (added in the third migration), enabling the same `meta_key` for different model instances.

### `HasMetas` Trait (`src/Traits/HasMetas.php`)

Add to any Eloquent model to attach `ModelMeta` records:

```php
use Osoobe\Laravel\Settings\Traits\HasMetas;

class User extends Model {
    use HasMetas;
}

// Usage
$user->getMeta('theme');           // returns ModelMeta record
$user->getMetaValue('theme');      // returns the typed value
$user->updateMeta('theme', 'dark');
$user->getSettingsByCategory();    // keyed by category
User::hasMetas('role', 'admin');   // query scope
```

### `EnumConfig` Trait (`src/Traits/EnumConfig.php`)

Apply to a PHP backed enum whose `value` is a meta key. Delegates to `AppMeta::config()` / `AppMeta::setConfig()` / `AppMeta::secret()` / `AppMeta::setSecret()` using the enum case's `value`:

```php
enum AppSettings: string {
    use EnumConfig;
    case SiteName = 'site.name';
}

AppSettings::SiteName->config('Default');
AppSettings::SiteName->setConfig('My App');
```

### `History` Trait (`src/Traits/History.php`)

Requires the model to also `use HasMetas`. Hooks into `created` and `updated` Eloquent events to append a change record to a `history` meta entry. The model must implement `metaTrack(): array` returning the field names to track. History entries are stored as an array of diffs in the `history` meta key.

### Service Provider (`src/LaravelSettingsServiceProvider.php`)

Auto-discovered via `composer.json` `extra.laravel.providers`. Only function: loads package migrations from `database/migrations/`.

### Testing

Tests use [Orchestra Testbench](https://github.com/orchestral/testbench). The base `TestCase` extends `Orchestra\Testbench\TestCase` and registers the service provider. **Note**: the existing `tests/TestCase.php` has a namespace bug — it references `\Osoobe\LaravelSettings\LaravelSettingsServiceProvider` instead of the correct `\Osoobe\Laravel\Settings\LaravelSettingsServiceProvider`.

## Conventions

- **PSR-2** coding standard enforced via StyleCI (Laravel preset).
- `meta_value` is always stored as a string in the DB; the `value` accessor handles type restoration.
- Never access `meta_value` directly on a model instance — always use the `value` accessor or the trait helper methods.
- New meta-bearing models should subclass `AppMeta` (for global settings) or use `HasMetas` (for per-model settings), not create new tables.
- Contributions must include tests; patches without tests will not be accepted.
