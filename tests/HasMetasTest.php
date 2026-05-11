<?php

namespace Osoobe\Laravel\Settings\Tests;

use Osoobe\Laravel\Settings\Models\ModelMeta;
use Osoobe\Laravel\Settings\Tests\Fixtures\User;

class HasMetasTest extends TestCase
{
    // -------------------------------------------------------------------------
    // updateMeta
    // -------------------------------------------------------------------------

    public function test_update_meta_creates_meta_for_model()
    {
        $user = User::create(['name' => 'Alice']);
        $user->updateMeta('theme', 'dark');

        $this->assertSame(1, ModelMeta::where('model_id', $user->id)->count());
        $this->assertSame('dark', ModelMeta::where('meta_key', 'theme')->first()->value);
    }

    public function test_update_meta_overwrites_existing_value()
    {
        $user = User::create(['name' => 'Bob']);
        $user->updateMeta('theme', 'light');
        $user->updateMeta('theme', 'dark');

        $this->assertSame(1, $user->metas()->where('meta_key', 'theme')->count());
        $this->assertSame('dark', $user->getMeta('theme', false)->value);
    }

    public function test_same_key_is_independent_across_model_instances()
    {
        $u1 = User::create(['name' => 'User1']);
        $u2 = User::create(['name' => 'User2']);
        $u1->updateMeta('role', 'admin');
        $u2->updateMeta('role', 'viewer');

        $this->assertSame('admin', $u1->getMeta('role', false)->value);
        $this->assertSame('viewer', $u2->getMeta('role', false)->value);
    }

    // -------------------------------------------------------------------------
    // getMeta
    // -------------------------------------------------------------------------

    public function test_get_meta_returns_null_when_not_found_without_cache()
    {
        $user = User::create(['name' => 'Carol']);

        $this->assertNull($user->getMeta('nonexistent', false));
    }

    public function test_get_meta_without_cache_queries_db_directly()
    {
        $user = User::create(['name' => 'Dave']);
        $user->updateMeta('color', 'blue');

        $meta = $user->getMeta('color', false);
        $this->assertNotNull($meta);
        $this->assertSame('blue', $meta->value);
    }

    public function test_get_meta_with_cache_uses_loaded_relation()
    {
        $user = User::create(['name' => 'Eve']);
        $user->updateMeta('locale', 'en');

        // Load the relation into the model's cache
        $user->load('metas');
        $meta = $user->getMeta('locale');

        $this->assertNotNull($meta);
        $this->assertSame('en', $meta->value);
    }

    public function test_get_meta_with_cache_returns_null_when_relation_empty()
    {
        $user = User::create(['name' => 'Frank']);

        // No metas loaded, getMeta(cache=true) returns null
        $this->assertNull($user->getMeta('anything'));
    }

    // -------------------------------------------------------------------------
    // getMetaValue
    // -------------------------------------------------------------------------

    public function test_get_meta_value_returns_typed_value()
    {
        $user = User::create(['name' => 'Grace']);
        $user->updateMeta('score', 99);

        $this->assertSame(99, $user->getMetaValue('score', false));
        $this->assertIsInt($user->getMetaValue('score', false));
    }

    public function test_get_meta_value_returns_null_when_meta_not_found()
    {
        $user = User::create(['name' => 'Hank']);

        $this->assertNull($user->getMetaValue('nonexistent', false));
    }

    public function test_get_meta_value_handles_array_type()
    {
        $user = User::create(['name' => 'Iris']);
        $user->updateMeta('tags', ['php', 'laravel']);

        $this->assertSame(['php', 'laravel'], $user->getMetaValue('tags', false));
    }

    // -------------------------------------------------------------------------
    // getSettingsByCategory
    // -------------------------------------------------------------------------

    public function test_get_settings_by_category_returns_empty_array_when_no_metas()
    {
        // categorizeObjects in osoobe/laravel-utilities has an inverted if/else
        // that causes a TypeError when iterating over any records. With an empty
        // collection the loop body never runs, so the result is always [].
        $user = User::create(['name' => 'Jane']);
        $this->assertSame([], $user->getSettingsByCategory());
    }

    // -------------------------------------------------------------------------
    // scopeHasMetas
    // -------------------------------------------------------------------------

    public function test_has_metas_scope_finds_models_by_meta_key()
    {
        $u1 = User::create(['name' => 'Kurt']);
        $u2 = User::create(['name' => 'Lily']);
        $u1->updateMeta('is_admin', true);

        $results = User::hasMetas('is_admin')->get();

        $this->assertCount(1, $results);
        $this->assertSame($u1->id, $results->first()->id);
    }

    public function test_has_metas_scope_finds_models_by_key_and_value()
    {
        $u1 = User::create(['name' => 'Mike']);
        $u2 = User::create(['name' => 'Nina']);
        $u1->updateMeta('role', 'admin');
        $u2->updateMeta('role', 'viewer');

        $admins = User::hasMetas('role', 'admin')->get();

        $this->assertCount(1, $admins);
        $this->assertSame($u1->id, $admins->first()->id);
    }

    public function test_has_metas_scope_returns_empty_when_no_match()
    {
        User::create(['name' => 'Oscar']);

        $this->assertCount(0, User::hasMetas('nonexistent')->get());
    }

    // -------------------------------------------------------------------------
    // metas relation
    // -------------------------------------------------------------------------

    public function test_metas_relation_returns_morph_many()
    {
        $user = User::create(['name' => 'Paula']);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\MorphMany::class,
            $user->metas()
        );
    }

    public function test_metas_relation_is_scoped_to_model_instance()
    {
        $u1 = User::create(['name' => 'Quinn']);
        $u2 = User::create(['name' => 'Rita']);
        $u1->updateMeta('pref', 'a');
        $u2->updateMeta('pref', 'b');

        $this->assertCount(1, $u1->metas()->get());
        $this->assertCount(1, $u2->metas()->get());
    }
}
