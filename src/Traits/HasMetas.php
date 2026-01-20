<?php

namespace Osoobe\Laravel\Settings\Traits;

use Osoobe\Laravel\Settings\Models\ModelMeta;
use Osoobe\Utilities\Helpers\Utilities;

trait HasMetas {

    /**
     * Get all of the object's links.
     */
    public function metas()
    {
        return $this->morphMany(ModelMeta::class, __FUNCTION__, 'model_type', 'model_id');
    }

    /**
     * Search model for related metas
     *
     * @param mixed $query
     * @param string $meta_key
     * @param mixed $meta_value
     * @return mixed|null
     */
    public function scopeHasMetas($query, string $meta_key, $meta_value=null) {
        return $query->whereHas('metas', function($sq) use($meta_key, $meta_value) {
            $sq->where('meta_key', $meta_key);
            if ( !empty($meta_value) ) {
                $sq->where('meta_value', $meta_value);
            }
        });
    }


    /**
     * Get meta for the given relationship
     *
     * @param string $key
     * @return mixed
     */
    public function getMeta(string $key, $cache=true) {
        if ( ! $cache ) {
            return $this->metas()
                ->where('meta_key', $key)
                ->first();
        }
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
    public function getMetaValue(string $key, $cache=true) {
        $meta = $this->getMeta($key, $cache);
        if ( ! $meta ) {
            return null;
        }
        return $meta->value;
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


    /**
     * Get application settings.
     *
     * @return mixed  Returns application settings.
     */
    public function getSettingsByCategory() {
        $metas = $this->metas()->orderBy('category', 'ASC')
            ->orderBy('meta_key', 'ASC')
            ->get();
        return Utilities::categorizeObjects($metas, 'category');
    }

}

?>
