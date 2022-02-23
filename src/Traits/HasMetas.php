<?php

namespace Osoobe\Laravel\Settings\Traits;

use Osoobe\Laravel\Settings\Models\ModelMeta;

trait HasMetas {

    /**
     * Get all of the object's links.
     */
    public function metas()
    {
        return $this->morphOne(ModelMeta::class, __FUNCTION__, 'model_type', 'model_id');
    }

    /**
     * Get meta for the given relationship
     *
     * @param string $key
     * @return mixed
     */
    public function getMeta(string $key) {
        if ( $this->metas ) {
            return $this->metas
                ->where('meta_key', $key)
                ->first();
        }
        return null;
    }


    /**
     * Get meta value for the given relationship
     *
     * @param string $key
     * @return mixed
     */
    public function getMetaValue(string $key) {
        $meta = $this->getMeta($key);
        if ( ! $meta ) {
            return null;
        }
        return $meta->meta_value;
    }

    /**
     * Update or create meta
     *
     * @param string $key
     * @param mixed $val
     * @return mixed
     */
    public function updateMeta(string $key, $val) {
        return $this->metas()->updateOrCreate(
            ['meta_key' =>  $key],
            [
                'meta_value' => $val
            ]
        );
    }

}

?>