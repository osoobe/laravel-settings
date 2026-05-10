# laravel-settings

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Travis](https://img.shields.io/travis/osoobe/laravel-settings.svg?style=flat-square)]()
[![Total Downloads](https://img.shields.io/packagist/dt/osoobe/laravel-settings.svg?style=flat-square)](https://packagist.org/packages/osoobe/laravel-settings)

A Laravel package for dynamically storing, overriding, and retrieving Laravel system configs and per-model settings at runtime — without touching `.env` or config files.

Values are persisted to the database, automatically type-cast on read, and can be cached or encrypted. The package ships two independent storage systems:

- **`AppMeta`** — global, application-wide key/value settings.
- **`ModelMeta`** — per-Eloquent-model key/value settings, attached via a polymorphic relation.

## Install

```bash
composer require osoobe/laravel-settings
```

The service provider is auto-discovered. Run the migrations to create the `app_metas` and `model_metas` tables:

```bash
php artisan migrate
```

---

## Use Cases

- **Override Laravel config at runtime** — store a value in `AppMeta` and retrieve it with `AppMeta::config('mail.from.address')`. The DB value takes precedence over `config()`, so you can change settings without a deploy.
- **Encrypted credentials** — store third-party API keys or passwords encrypted in the DB with `AppMeta::setSecret()` and retrieve them decrypted with `AppMeta::secret()`.
- **Per-user or per-model settings** — attach arbitrary key/value pairs to any Eloquent model (e.g. user preferences, per-tenant config) using the `HasMetas` trait.
- **Feature flags** — store boolean flags in `AppMeta` and read them anywhere in the application.
- **Versioned or grouped settings** — subclass `AppMeta` with a custom `CATEGORY` constant to namespace settings for a specific domain (e.g. `PaymentSettings`, `MailSettings`).
- **Audit / change history** — use the `History` trait on any `HasMetas` model to record a diff log of field changes automatically.

---

## AppMeta — Global Application Settings

`AppMeta` stores application-wide key/value pairs in the `app_metas` table.

### Dynamic Laravel Config Override

`AppMeta::config()` checks the database first and falls back to Laravel's `config()`. This means you can override any config value at runtime without changing files or redeploying.

```php
use Osoobe\Laravel\Settings\Models\AppMeta;

// Read: DB value takes precedence over config files
$name = AppMeta::config('app.name', 'My App');

// Write: persists to DB and updates the runtime config() value
AppMeta::setConfig('app.name', 'My New App');

// Later in the same request, config('app.name') also returns 'My New App'
```

### Encrypted Secrets

Use `secret` / `setSecret` for values that should be stored encrypted (API keys, passwords, tokens). The value is encrypted with Laravel's `encrypt()` before being written and decrypted transparently on read.

```php
// Store an API key encrypted
AppMeta::setSecret('services.stripe.secret', 'sk_live_...');

// Retrieve decrypted; falls back to config('services.stripe.secret') if not in DB
$key = AppMeta::secret('services.stripe.secret');
```

### Basic CRUD

```php
// Read a value (returns the typed PHP value, not the raw string)
AppMeta::getMeta('site.maintenance', false);

// Write (upsert)
AppMeta::updateMeta('site.maintenance', true);

// Read or seed if missing
AppMeta::getOrCreateMeta('site.launch_date', '2024-01-01');

// Check existence
AppMeta::keyExists('site.maintenance'); // bool

// Read with permanent cache (auto-creates record if missing)
AppMeta::getMetaOrCached('site.maintenance', false);

// Write and update cache atomically
AppMeta::updateOrCreateWithCache('site.maintenance', false);

// All settings grouped by category
AppMeta::getAppSettingsByCategory();
```

### Category Scoping via Subclassing

Subclass `AppMeta` and set a `CATEGORY` constant to namespace a group of settings. All queries on the subclass are automatically scoped to that category.

```php
class MailSettings extends AppMeta {
    const CATEGORY = 'mail';

    public static function getKeyPrefix(): string {
        return 'mail_meta_';
    }
}

MailSettings::setConfig('mail.from.address', 'hello@example.com');
MailSettings::config('mail.from.address'); // only searches the 'mail' category
```

---

## ModelMeta — Per-Model Settings

`ModelMeta` stores key/value pairs scoped to an individual Eloquent model instance in the `model_metas` table. Uniqueness is composite on `(model_id, model_type, meta_key)`.

Add the `HasMetas` trait to any Eloquent model:

```php
use Osoobe\Laravel\Settings\Traits\HasMetas;

class User extends Model {
    use HasMetas;
}
```

### Usage

```php
$user = User::find(1);

// Read a typed value directly
$theme = $user->getMetaValue('theme'); // e.g. 'dark'

// Read the ModelMeta record
$meta = $user->getMeta('theme');

// Write (upsert)
$user->updateMeta('theme', 'dark');

// All settings grouped by category
$user->getSettingsByCategory();

// Query scope: find users with a specific meta key/value
User::hasMetas('role', 'admin')->get();
User::hasMetas('newsletter')->get(); // key exists, any value
```

---

## EnumConfig — Enum-Driven Config Keys

Apply `EnumConfig` to a PHP backed enum whose `value` is a config/meta key. This gives you a type-safe API for all your application settings.

```php
use Osoobe\Laravel\Settings\Traits\EnumConfig;

enum AppSettings: string {
    use EnumConfig;

    case SiteName    = 'app.name';
    case MaintenanceMode = 'app.maintenance';
    case StripeKey   = 'services.stripe.secret';
}

// Read (DB overrides config file)
AppSettings::SiteName->config('Default Name');

// Write (persists to DB + updates runtime config)
AppSettings::SiteName->setConfig('My App');

// Encrypted secret
AppSettings::StripeKey->setSecret('sk_live_...');
AppSettings::StripeKey->secret();
```

---

## History — Automatic Change Tracking

Add the `History` trait to any model that already uses `HasMetas`. It hooks into `created` and `updated` Eloquent events and appends a diff to a `history` meta entry. Implement `metaTrack()` to declare which fields to watch.

```php
use Osoobe\Laravel\Settings\Traits\HasMetas;
use Osoobe\Laravel\Settings\Traits\History;

class Order extends Model {
    use HasMetas, History;

    public function metaTrack(): array {
        return ['status', 'total'];
    }
}

// Retrieve the full history array
$order->getMetaHistory();
```

Each history entry contains the tracked field values at the time of the event, a `history_status` (`"created"` or `"updated"`), the `updated_at` timestamp, and a Unix `timestamp`.

---

## Type Handling

Values are always stored as strings in the database. The `meta_type` column records the PHP type (`string`, `integer`, `boolean`, `array`, etc.) and is set automatically on every write. Reading a record via the `value` accessor or any helper method returns the correctly typed PHP value — arrays are JSON-encoded on write and decoded on read.

**Never read `meta_value` directly.** Always use the `value` accessor or the provided helper methods.

---

## Caching

`getMetaOrCached()` stores values in `Cache::forever()` and auto-creates the DB record if it doesn't exist. The cache key is `{prefix}{meta_key}` — `app_meta_` for `AppMeta` and `model_meta_` for `ModelMeta`. Override `getKeyPrefix()` on a subclass to avoid collisions.

```php
// Reads from cache; writes to cache + DB on first call
AppMeta::getMetaOrCached('site.name', 'My App');

// Write and refresh cache
AppMeta::updateOrCreateWithCache('site.name', 'New Name');
```

---

## Testing

```bash
composer test
# or
vendor/bin/phpunit
```

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Oshane Bailey](https://github.com/osoobe)
- [All Contributors](https://github.com/osoobe/laravel-settings/contributors)

## Security

If you discover any security-related issues, please email b4.oshany@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.
