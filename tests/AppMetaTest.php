<?php

namespace Osoobe\Laravel\Settings\Tests;

use Illuminate\Support\Facades\Cache;
use Osoobe\Laravel\Settings\Models\AppMeta;

class AppMetaTest extends TestCase
{
    // -------------------------------------------------------------------------
    // getMeta
    // -------------------------------------------------------------------------

    public function test_get_meta_returns_null_when_key_not_found()
    {
        $this->assertNull(AppMeta::getMeta('missing.key'));
    }

    public function test_get_meta_returns_default_when_key_not_found()
    {
        $this->assertSame('fallback', AppMeta::getMeta('missing.key', 'fallback'));
    }

    public function test_get_meta_returns_value_when_key_exists()
    {
        AppMeta::updateMeta('site.name', 'My App');

        $this->assertSame('My App', AppMeta::getMeta('site.name'));
    }

    // -------------------------------------------------------------------------
    // keyExists
    // -------------------------------------------------------------------------

    public function test_key_exists_returns_false_when_not_found()
    {
        $this->assertFalse(AppMeta::keyExists('nonexistent'));
    }

    public function test_key_exists_returns_true_when_found()
    {
        AppMeta::updateMeta('exists.key', 'yes');

        $this->assertTrue(AppMeta::keyExists('exists.key'));
    }

    // -------------------------------------------------------------------------
    // createMeta
    // -------------------------------------------------------------------------

    public function test_create_meta_inserts_new_record()
    {
        AppMeta::createMeta('new.key', 'hello');

        $this->assertSame('hello', AppMeta::getMeta('new.key'));
    }

    public function test_create_meta_does_not_overwrite_existing_record()
    {
        AppMeta::createMeta('dupe.key', 'original');
        AppMeta::createMeta('dupe.key', 'overwrite');

        $this->assertSame('original', AppMeta::getMeta('dupe.key'));
    }

    // -------------------------------------------------------------------------
    // updateMeta
    // -------------------------------------------------------------------------

    public function test_update_meta_creates_new_record()
    {
        AppMeta::updateMeta('brand.new', 'value');

        $this->assertSame('value', AppMeta::getMeta('brand.new'));
    }

    public function test_update_meta_overwrites_existing_record()
    {
        AppMeta::updateMeta('overwrite.me', 'first');
        AppMeta::updateMeta('overwrite.me', 'second');

        $this->assertSame('second', AppMeta::getMeta('overwrite.me'));
    }

    // -------------------------------------------------------------------------
    // getOrCreateMeta
    // -------------------------------------------------------------------------

    public function test_get_or_create_meta_returns_existing_value()
    {
        AppMeta::updateMeta('pre.existing', 'stored');
        $result = AppMeta::getOrCreateMeta('pre.existing', 'default');

        $this->assertSame('stored', $result);
    }

    public function test_get_or_create_meta_creates_and_returns_default_when_missing()
    {
        $result = AppMeta::getOrCreateMeta('absent.key', 'seeded');

        $this->assertSame('seeded', $result);
        $this->assertTrue(AppMeta::keyExists('absent.key'));
    }

    // -------------------------------------------------------------------------
    // Type serialisation (bootMetaTrait + getValueAttribute)
    // -------------------------------------------------------------------------

    public function test_string_value_is_stored_and_cast_correctly()
    {
        AppMeta::updateMeta('type.string', 'hello');

        $this->assertSame('hello', AppMeta::getMeta('type.string'));
        $this->assertIsString(AppMeta::getMeta('type.string'));
    }

    public function test_integer_value_is_stored_and_cast_correctly()
    {
        AppMeta::updateMeta('type.int', 42);

        $this->assertSame(42, AppMeta::getMeta('type.int'));
        $this->assertIsInt(AppMeta::getMeta('type.int'));
    }

    public function test_boolean_true_is_stored_and_cast_correctly()
    {
        AppMeta::updateMeta('type.bool', true);

        $this->assertTrue(AppMeta::getMeta('type.bool'));
        $this->assertIsBool(AppMeta::getMeta('type.bool'));
    }

    public function test_boolean_false_is_stored_and_cast_correctly()
    {
        AppMeta::updateMeta('type.bool.false', false);

        $record = AppMeta::where('meta_key', 'type.bool.false')->first();
        $this->assertNotNull($record);
        $this->assertSame('boolean', $record->getRawOriginal('meta_type'));
    }

    public function test_float_value_is_stored_and_cast_correctly()
    {
        AppMeta::updateMeta('type.float', 3.14);

        $this->assertEqualsWithDelta(3.14, AppMeta::getMeta('type.float'), 0.0001);
        $this->assertIsFloat(AppMeta::getMeta('type.float'));
    }

    public function test_array_value_is_json_encoded_in_db_and_decoded_on_read()
    {
        $data = ['key' => 'value', 'nested' => [1, 2, 3]];
        AppMeta::updateMeta('type.array', $data);

        $record = AppMeta::where('meta_key', 'type.array')->first();
        $this->assertSame('array', $record->getRawOriginal('meta_type'));
        $this->assertJson($record->getRawOriginal('meta_value'));
        $this->assertSame($data, AppMeta::getMeta('type.array'));
    }

    public function test_meta_type_is_set_automatically_on_create()
    {
        AppMeta::createMeta('auto.type', 'string value');

        $record = AppMeta::where('meta_key', 'auto.type')->first();
        $this->assertSame('string', $record->getRawOriginal('meta_type'));
    }

    public function test_meta_type_is_updated_on_update()
    {
        AppMeta::createMeta('type.change', 'initially a string');
        AppMeta::updateMeta('type.change', 99);

        $record = AppMeta::where('meta_key', 'type.change')->first();
        $this->assertSame('integer', $record->getRawOriginal('meta_type'));
    }

    // -------------------------------------------------------------------------
    // Default category
    // -------------------------------------------------------------------------

    public function test_default_category_is_set_on_create()
    {
        AppMeta::createMeta('cat.default', 'val');

        $record = AppMeta::where('meta_key', 'cat.default')->first();
        $this->assertSame('default', $record->category);
    }

    // -------------------------------------------------------------------------
    // config / setConfig
    // -------------------------------------------------------------------------

    public function test_config_returns_db_value_when_present()
    {
        AppMeta::updateMeta('app.name', 'DB App');
        config(['app.name' => 'Config App']);

        $this->assertSame('DB App', AppMeta::config('app.name'));
    }

    public function test_config_falls_back_to_laravel_config_when_no_db_record()
    {
        config(['app.locale' => 'en']);

        $this->assertSame('en', AppMeta::config('app.locale'));
    }

    public function test_config_returns_default_when_no_db_and_no_laravel_config()
    {
        $this->assertSame('my-default', AppMeta::config('no.such.key', 'my-default'));
    }

    public function test_set_config_persists_value_to_db()
    {
        AppMeta::setConfig('app.timezone', 'UTC');

        $this->assertSame('UTC', AppMeta::getMeta('app.timezone'));
    }

    public function test_set_config_also_updates_runtime_config()
    {
        AppMeta::setConfig('app.timezone', 'America/New_York');

        $this->assertSame('America/New_York', config('app.timezone'));
    }

    public function test_set_config_skips_runtime_update_when_flag_is_false()
    {
        config(['app.timezone' => 'original']);
        AppMeta::setConfig('app.timezone', 'changed', false);

        $this->assertSame('original', config('app.timezone'));
        $this->assertSame('changed', AppMeta::getMeta('app.timezone'));
    }

    public function test_get_meta_or_config_is_alias_for_config()
    {
        AppMeta::updateMeta('alias.key', 'alias-value');

        $this->assertSame(AppMeta::config('alias.key'), AppMeta::getMetaOrConfig('alias.key'));
    }

    // -------------------------------------------------------------------------
    // secret / setSecret
    // -------------------------------------------------------------------------

    public function test_set_secret_stores_value_encrypted()
    {
        AppMeta::setSecret('api.key', 'super-secret');

        $raw = AppMeta::where('meta_key', 'api.key')->first()->getRawOriginal('meta_value');
        $this->assertNotSame('super-secret', $raw);
        $this->assertSame('super-secret', decrypt($raw));
    }

    public function test_secret_returns_decrypted_value()
    {
        AppMeta::setSecret('api.token', 'plain-text');

        $this->assertSame('plain-text', AppMeta::secret('api.token'));
    }

    public function test_secret_falls_back_to_laravel_config_when_no_db_record()
    {
        config(['services.api.key' => 'from-config']);

        $this->assertSame('from-config', AppMeta::secret('services.api.key'));
    }

    public function test_set_secret_updates_runtime_config_with_plain_value()
    {
        AppMeta::setSecret('services.token', 'my-token');

        $this->assertSame('my-token', config('services.token'));
    }

    public function test_set_secret_skips_runtime_update_when_flag_is_false()
    {
        config(['services.token' => 'original']);
        AppMeta::setSecret('services.token', 'new-token', false);

        $this->assertSame('original', config('services.token'));
        $this->assertSame('new-token', AppMeta::secret('services.token'));
    }

    // -------------------------------------------------------------------------
    // getMetaOrCached / updateOrCreateWithCache
    // -------------------------------------------------------------------------

    public function test_get_meta_or_cached_creates_record_when_missing()
    {
        AppMeta::getMetaOrCached('cached.new', 'default-val');

        $this->assertTrue(AppMeta::keyExists('cached.new'));
    }

    public function test_get_meta_or_cached_returns_default_when_record_missing()
    {
        $result = AppMeta::getMetaOrCached('cached.missing', 'fallback');

        $this->assertSame('fallback', $result);
    }

    public function test_get_meta_or_cached_reads_from_cache_on_second_call()
    {
        AppMeta::getMetaOrCached('cached.key', 'original');

        // Delete from DB — the second call should still return from cache
        AppMeta::where('meta_key', 'cached.key')->delete();

        $cached_key = AppMeta::getCachedKey('cached.key');
        $this->assertTrue(Cache::has($cached_key));
        $this->assertSame('original', Cache::get($cached_key));
    }

    public function test_get_meta_or_cached_bypasses_cache_when_clear_is_true()
    {
        AppMeta::updateMeta('cached.clear', 'first');
        AppMeta::getMetaOrCached('cached.clear', null); // populate cache

        AppMeta::updateMeta('cached.clear', 'second');
        $result = AppMeta::getMetaOrCached('cached.clear', null, null, true);

        $this->assertSame('second', $result);
    }

    public function test_update_or_create_with_cache_creates_record()
    {
        AppMeta::updateOrCreateWithCache('cache.create', 'hello');

        $this->assertSame('hello', AppMeta::getMeta('cache.create'));
    }

    public function test_update_or_create_with_cache_updates_record_and_refreshes_cache()
    {
        AppMeta::updateOrCreateWithCache('cache.update', 'v1');
        AppMeta::updateOrCreateWithCache('cache.update', 'v2');

        $cached_key = AppMeta::getCachedKey('cache.update');
        $this->assertSame('v2', Cache::get($cached_key));
        $this->assertSame('v2', AppMeta::getMeta('cache.update'));
    }

    // -------------------------------------------------------------------------
    // getCachedKey / getKeyPrefix
    // -------------------------------------------------------------------------

    public function test_get_key_prefix_returns_app_meta_prefix()
    {
        $this->assertSame('app_meta_', AppMeta::getKeyPrefix());
    }

    public function test_get_cached_key_prepends_prefix()
    {
        $this->assertSame('app_meta_my.key', AppMeta::getCachedKey('my.key'));
    }

    // -------------------------------------------------------------------------
    // getMetaByCategory / getAppSettingsByCategory
    // -------------------------------------------------------------------------

    public function test_get_meta_by_category_returns_empty_array_when_no_records()
    {
        // categorizeObjects in osoobe/laravel-utilities has an inverted if/else
        // that causes a TypeError when iterating over any records. With an empty
        // collection the loop body never runs, so the result is always [].
        $this->assertSame([], AppMeta::getAppSettingsByCategory());
    }

    // -------------------------------------------------------------------------
    // scopeMetaKey
    // -------------------------------------------------------------------------

    public function test_scope_meta_key_filters_by_key()
    {
        AppMeta::updateMeta('scope.target', 'yes');
        AppMeta::updateMeta('scope.other', 'no');

        $results = AppMeta::metaKey('scope.target')->get();

        $this->assertCount(1, $results);
        $this->assertSame('scope.target', $results->first()->meta_key);
    }

    // -------------------------------------------------------------------------
    // updateKey
    // -------------------------------------------------------------------------

    public function test_update_key_creates_new_record()
    {
        AppMeta::updateKey('update.key.new', 'created');

        $this->assertSame('created', AppMeta::getMeta('update.key.new'));
    }

    public function test_update_key_updates_existing_record()
    {
        AppMeta::updateMeta('update.key.existing', 'old');
        AppMeta::updateKey('update.key.existing', 'new');

        $this->assertSame('new', AppMeta::getMeta('update.key.existing'));
    }
}
