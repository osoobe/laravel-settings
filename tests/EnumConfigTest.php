<?php

namespace Osoobe\Laravel\Settings\Tests;

use Osoobe\Laravel\Settings\Models\AppMeta;
use Osoobe\Laravel\Settings\Tests\Fixtures\AppSettingsEnum;

class EnumConfigTest extends TestCase
{
    // -------------------------------------------------------------------------
    // config / setConfig
    // -------------------------------------------------------------------------

    public function test_set_config_writes_value_to_app_meta()
    {
        AppSettingsEnum::SiteName->setConfig('My Site');

        $this->assertSame('My Site', AppMeta::getMeta('app.name'));
    }

    public function test_config_reads_value_written_by_set_config()
    {
        AppSettingsEnum::SiteName->setConfig('Enum App');

        $this->assertSame('Enum App', AppSettingsEnum::SiteName->config());
    }

    public function test_config_returns_default_when_no_db_and_no_laravel_config()
    {
        // MaxItems maps to 'app.max_items' which is not a standard Laravel config key.
        // SiteName maps to 'app.name' which Testbench sets to 'Laravel' by default.
        $this->assertSame('default-val', AppSettingsEnum::MaxItems->config('default-val'));
    }

    public function test_config_returns_laravel_config_fallback_when_no_db_record()
    {
        config(['app.name' => 'Laravel App']);

        $this->assertSame('Laravel App', AppSettingsEnum::SiteName->config('default'));
    }

    public function test_config_prefers_db_value_over_laravel_config()
    {
        config(['app.name' => 'Laravel App']);
        AppSettingsEnum::SiteName->setConfig('DB App');

        $this->assertSame('DB App', AppSettingsEnum::SiteName->config('default'));
    }

    public function test_set_config_updates_runtime_laravel_config()
    {
        AppSettingsEnum::SiteName->setConfig('Runtime Name');

        $this->assertSame('Runtime Name', config('app.name'));
    }

    // -------------------------------------------------------------------------
    // secret / setSecret
    // -------------------------------------------------------------------------

    public function test_set_secret_stores_value_encrypted_in_app_meta()
    {
        AppSettingsEnum::ApiKey->setSecret('secret-token');

        $raw = AppMeta::where('meta_key', 'services.api.key')->first()->getRawOriginal('meta_value');
        $this->assertNotSame('secret-token', $raw);
        $this->assertSame('secret-token', decrypt($raw));
    }

    public function test_secret_returns_decrypted_value()
    {
        AppSettingsEnum::ApiKey->setSecret('my-api-key');

        $this->assertSame('my-api-key', AppSettingsEnum::ApiKey->secret());
    }

    public function test_secret_returns_null_default_when_no_record_and_no_config()
    {
        $this->assertNull(AppSettingsEnum::ApiKey->secret());
    }

    public function test_secret_falls_back_to_laravel_config()
    {
        config(['services.api.key' => 'from-config-key']);

        $this->assertSame('from-config-key', AppSettingsEnum::ApiKey->secret());
    }

    // -------------------------------------------------------------------------
    // enum value is used as meta key
    // -------------------------------------------------------------------------

    public function test_enum_value_is_used_as_meta_key()
    {
        AppSettingsEnum::MaxItems->setConfig(50);

        $this->assertTrue(AppMeta::keyExists('app.max_items'));
        $this->assertSame(50, AppMeta::getMeta('app.max_items'));
    }

    public function test_multiple_enum_cases_are_independent()
    {
        AppSettingsEnum::SiteName->setConfig('My Site');
        AppSettingsEnum::MaxItems->setConfig(100);

        $this->assertSame('My Site', AppSettingsEnum::SiteName->config());
        $this->assertSame(100, AppSettingsEnum::MaxItems->config());
    }
}
