<?php

namespace Osoobe\Laravel\Settings\Tests;

use Osoobe\Laravel\Settings\Models\ModelMeta;

class ModelMetaTest extends TestCase
{
    public function test_table_name_is_model_metas()
    {
        $this->assertSame('model_metas', (new ModelMeta())->getTable());
    }

    public function test_get_key_prefix_returns_model_meta_prefix()
    {
        $this->assertSame('model_meta_', ModelMeta::getKeyPrefix());
    }

    public function test_get_cached_key_uses_model_meta_prefix()
    {
        $this->assertSame('model_meta_some.key', ModelMeta::getCachedKey('some.key'));
    }

    public function test_model_meta_can_be_created_directly()
    {
        ModelMeta::create([
            'model_id'   => 1,
            'model_type' => 'App\\Models\\User',
            'meta_key'   => 'direct.key',
            'meta_value' => 'direct-value',
        ]);

        $record = ModelMeta::where('meta_key', 'direct.key')->first();
        $this->assertNotNull($record);
        $this->assertSame('direct-value', $record->value);
    }

    public function test_model_meta_allows_same_key_for_different_model_instances()
    {
        ModelMeta::create([
            'model_id'   => 1,
            'model_type' => 'App\\Models\\User',
            'meta_key'   => 'shared.key',
            'meta_value' => 'user-1',
        ]);
        ModelMeta::create([
            'model_id'   => 2,
            'model_type' => 'App\\Models\\User',
            'meta_key'   => 'shared.key',
            'meta_value' => 'user-2',
        ]);

        $this->assertSame(2, ModelMeta::where('meta_key', 'shared.key')->count());
    }

    public function test_model_meta_allows_same_key_for_different_model_types()
    {
        ModelMeta::create([
            'model_id'   => 1,
            'model_type' => 'App\\Models\\User',
            'meta_key'   => 'cross.type.key',
            'meta_value' => 'user-val',
        ]);
        ModelMeta::create([
            'model_id'   => 1,
            'model_type' => 'App\\Models\\Post',
            'meta_key'   => 'cross.type.key',
            'meta_value' => 'post-val',
        ]);

        $this->assertSame(2, ModelMeta::where('meta_key', 'cross.type.key')->count());
    }

    public function test_meta_type_is_set_automatically_on_create()
    {
        ModelMeta::create([
            'model_id'   => 1,
            'model_type' => 'App\\Models\\User',
            'meta_key'   => 'typed.key',
            'meta_value' => 123,
        ]);

        $record = ModelMeta::where('meta_key', 'typed.key')->first();
        $this->assertSame('integer', $record->getRawOriginal('meta_type'));
    }

    public function test_array_value_is_json_encoded_and_decoded()
    {
        $payload = ['a' => 1, 'b' => [2, 3]];
        ModelMeta::create([
            'model_id'   => 1,
            'model_type' => 'App\\Models\\User',
            'meta_key'   => 'array.key',
            'meta_value' => $payload,
        ]);

        $record = ModelMeta::where('meta_key', 'array.key')->first();
        $this->assertSame('array', $record->getRawOriginal('meta_type'));
        $this->assertSame($payload, $record->value);
    }

    public function test_modellable_morph_relation_resolves()
    {
        // Instantiate without model_type so MorphTo doesn't try to resolve a class.
        $meta = new ModelMeta();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\MorphTo::class,
            $meta->modellable()
        );
    }
}
