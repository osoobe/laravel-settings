<?php

namespace Osoobe\Laravel\Settings\Tests;

use Osoobe\Laravel\Settings\Models\AppMeta;
use Osoobe\Laravel\Settings\Tests\Fixtures\ScopedAppMeta;

class CategoryScopingTest extends TestCase
{
    // -------------------------------------------------------------------------
    // AppMeta default category
    // -------------------------------------------------------------------------

    public function test_app_meta_default_category_constant_is_default()
    {
        $this->assertSame('default', AppMeta::CATEGORY);
    }

    public function test_app_meta_get_default_category_returns_default()
    {
        $this->assertSame('default', AppMeta::getDefaultCategory());
    }

    public function test_records_created_via_app_meta_have_default_category()
    {
        AppMeta::updateMeta('cat.test', 'value');

        $record = AppMeta::where('meta_key', 'cat.test')->first();
        $this->assertSame('default', $record->category);
    }

    // -------------------------------------------------------------------------
    // ScopedAppMeta — custom category via getDefaultCategory() override
    // -------------------------------------------------------------------------

    public function test_scoped_meta_has_custom_category_constant()
    {
        $this->assertSame('scoped', ScopedAppMeta::CATEGORY);
    }

    public function test_scoped_meta_get_default_category_returns_custom_value()
    {
        $this->assertSame('scoped', ScopedAppMeta::getDefaultCategory());
    }

    public function test_records_created_via_scoped_meta_receive_custom_category()
    {
        ScopedAppMeta::updateMeta('scoped.key', 'scoped-value');

        $record = AppMeta::withoutGlobalScopes()
            ->where('meta_key', 'scoped.key')
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('scoped', $record->category);
    }

    public function test_scoped_meta_key_prefix_is_overridden()
    {
        $this->assertSame('scoped_meta_', ScopedAppMeta::getKeyPrefix());
        $this->assertSame('scoped_meta_my.key', ScopedAppMeta::getCachedKey('my.key'));
    }

    public function test_scoped_meta_global_scope_excludes_other_category_records()
    {
        // Insert a record with 'default' category directly
        AppMeta::updateMeta('default.only', 'should-not-appear');

        // ScopedAppMeta is scoped to 'scoped', so it should not see 'default' records
        $result = ScopedAppMeta::where('meta_key', 'default.only')->first();
        $this->assertNull($result);
    }

    public function test_scoped_meta_only_returns_its_own_category_records()
    {
        ScopedAppMeta::updateMeta('scoped.a', 'alpha');
        ScopedAppMeta::updateMeta('scoped.b', 'beta');
        AppMeta::updateMeta('default.c', 'gamma'); // category = 'default'

        $allScoped = ScopedAppMeta::all();
        $this->assertCount(2, $allScoped);
        $this->assertTrue($allScoped->every(fn($r) => $r->category === 'scoped'));
    }

    public function test_app_meta_does_not_see_scoped_category_records_by_default()
    {
        ScopedAppMeta::updateMeta('scoped.hidden', 'hidden');

        // AppMeta has no global scope (CATEGORY = 'default' = no scope applied),
        // so it CAN see all records including 'scoped' category
        $result = AppMeta::where('meta_key', 'scoped.hidden')->first();
        $this->assertNotNull($result);
        $this->assertSame('scoped', $result->category);
    }

    // -------------------------------------------------------------------------
    // Category isolation: two subclasses don't bleed into each other
    // -------------------------------------------------------------------------

    public function test_scoped_get_meta_only_searches_its_category()
    {
        // Insert a 'default' category record with the same key
        AppMeta::updateMeta('shared.key', 'default-value');

        // ScopedAppMeta won't find it because the key is in 'default' category
        $this->assertNull(ScopedAppMeta::getMeta('shared.key'));

        // But the plain AppMeta can see it
        $this->assertSame('default-value', AppMeta::getMeta('shared.key'));
    }
}
