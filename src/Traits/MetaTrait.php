<?php


namespace Osoobe\Laravel\Settings\Traits;

use Illuminate\Support\Facades\Cache;
use Osoobe\Utilities\Helpers\Utilities;

trait MetaTrait {

    /**
     * Get meta by category.
     *
     * @return mixed        Returns application settings.
     */
    public static function getMetaByCategory() {
        $metas = static::orderBy('category', 'ASC')
            ->orderBy('meta_key', 'ASC')
            ->get();

        return Utilities::categorizeObjects($metas, 'category');
    }

    /**
     * Get meta value from the database
     *
     * @param string $key
     * @return mixed
     */
    public static function getMeta(string $key) {
        $data = static::where('meta_key', $key)->first();
        if(!empty($data)) {
            $type = $data->meta_type;
            $val = $data->meta_value;
            if ( $type == "array" ) {
                $val = json_decode($val, true);
            }
            return $val;
        }
        return null;
    }

    /**
     * Return meta value from the database or the value form the config file.
     *
     * @param string $key           Key for the config function
     * @param string $default       Default value if no meta value or config key found
     * @return mixed
     */
    public static function getMetaOrConfig(string $key, $default=null) {
        $meta = static::getMeta($key);
        if ( $meta != null ) {
            return $meta;
        }

        return config($key, $default);
    }


    /**
     * Get setting from cache or database.
     *
     * @param string $key          Application setting key
     * @param mixed $defaul        Default value to return.
     * @param mixed $type          Cast application setting value.
     * @return mixed               Returns the application setting value
     */
    public static function getMetaOrCached(string $key, $default=null, $type=null, $clear=false) {
        $val = $default;
        if ( empty($type) ) {
            $type = gettype($default);
        }
        $cached_key = static::getCachedKey($key);
        if (! $clear && Cache::has($cached_key)) {
            $val = Cache::get($cached_key);
        } else {
            $data = static::where('meta_key', $key)->first();
            if(!empty($data)) {
                $type = $data->meta_type ?? $type;
                $val = $data->meta_value;
            } else {
                static::create(
                    [
                        'meta_key' =>  $key,
                        'meta_value' => $val,
                        'meta_type' => $type
                    ]
                );
            }
            Cache::forever($cached_key, $val);
        }
        if ( !empty($val) && !empty($type) ) {
            if ( is_string($val) && $type == "array" ) {
                $val = json_decode($val, true);
            }
            settype($val, $type);
            return $val;
        }
        return $val;
    }

    /**
     * Update or create application setting then cache it.
     *
     * @param string $key
     * @param mixed $val
     * @return void
     */
    public static function updateOrCreateWithCache(string $key, $val, $data=null, $type=null) {
        $cached_key = static::getCachedKey($key);
        static::updateOrCreate(
            ['meta_key' =>  $key],
            [
                'meta_value' => $val,
                'meta_type' => $type
            ]
        );
        Cache::forever($cached_key, $val);
    }

    /**
     * Update or create meta
     *
     * @param string $key
     * @param mixed $val
     * @return mixed
     */
    public static function updateKey(string $key, $val) {
        return static::updateOrCreate(
            ['meta_key' =>  $key],
            [
                'meta_value' => $val
            ]
        );
    }


    /**
     * Create Notification Settings.
     *
     * @todo create notification setting package.
     * @return void
     */
    protected static function bootMetaTrait(): void {
        static::creating(function ($model) {
            $model->meta_type = gettype($model->meta_value);
            if ( $model->meta_type == "array" && is_array($model->meta_value) ) {
                $model->meta_value = json_encode($model->meta_value);
            }
        });
        static::updating(function ($model) {
            $model->meta_type = gettype($model->meta_value);
            if ( $model->meta_type == "array" && is_array($model->meta_value) ) {
                $model->meta_value = json_encode($model->meta_value);
            }
        });
    }

    /**
     * Get key used by memcache.
     *
     * @param string $key          Application setting key
     * @return string
     */
    public static function getCachedKey(string $key): string {
        return static::getKeyPrefix()."$key";
    }

    /**
     * Get key prefix
     *
     * @return string
     */
    public static function getKeyPrefix(): string {
        return "app_meta_";
    }

    public static function scopeMetaKey($query, $key) {
        return $query->where('meta_key', $key);
    }

}



?>