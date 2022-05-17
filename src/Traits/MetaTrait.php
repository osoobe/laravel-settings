<?php


namespace Osoobe\Laravel\Settings\Traits;

use Illuminate\Database\Eloquent\Builder;
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
            return $data->value;
        }
        return null;
    }

    /**
     * Get or create meta
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getOrCreateMeta(string $key, $default) {
        $val = static::getMeta($key);
        if ( !empty($val) ) {
            return $val;
        }

        static::createMeta($key, $val);
        return $default;
    }

    /**
     * Update or create meta
     *
     * @param string $key
     * @param mixed $val
     * @return mixed
     */
    public static function createMeta(string $key, $val) {
        return static::firstOrCreate(
            ['meta_key' =>  $key],
            [
                'meta_value' => $val
            ]
        );
    }

    /**
     * Update or create meta
     *
     * @param string $key
     * @param mixed $val
     * @return mixed
     */
    public static function updateMeta(string $key, $val) {
        return static::updateOrCreate(
            ['meta_key' =>  $key],
            [
                'meta_value' => $val
            ]
        );
    }


    /**
     * Get the meta value based on the given type
     *
     * @return mixed
     */
    public function getValueAttribute() {
        $type = $this->getRawOriginal('meta_type');
        $val = $this->getRawOriginal('meta_value');
        if ( $type == 'array' ) {
            return json_decode($val, true);
        } elseif ( !empty($type) ) {
            settype($val, $type);
        }
        return $val;
    }

    /**
     * Return meta value from the database or the value form the config file.
     *
     * @deprecated 1.5.0
     * @param string $key           Key for the config function
     * @param string $default       Default value if no meta value or config key found
     * @return mixed
     */
    public static function getMetaOrConfig(string $key, $default=null) {
        return static::config($key, $default);
    }

    /**
     * Return meta value from the database or the value form the config file.
     *
     * @param string $key           Key for the config function
     * @param string $default       Default value if no meta value or config key found
     * @return mixed
     */
    public static function config(string $key, $default=null) {
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

    public static function getDefaultCategory() {
        return "default";
    }

    /**
     * Create Notification Settings.
     *
     * @todo create notification setting package.
     * @return void
     */
    protected static function bootMetaTrait(): void {
        $default_category = self::getDefaultCategory();

        // If the default category of the model is not "default",
        // then filter out categories based on the model default category
        if ( $default_category != "default" ) {
            static::addGlobalScope('metaDefaultCategory', function (Builder $builder) use($default_category) {
                $builder->where('category', $default_category);
            });
        }

        $func = function ($model) use($default_category) {
            $model->meta_type = gettype($model->meta_value);
            if ( $model->meta_type == "array" && is_array($model->meta_value) ) {
                $model->meta_value = json_encode($model->meta_value);
            }

            if ( empty($model->category) ) {
                $model->category = $default_category;
            }
        };

        static::creating($func);
        static::updating($func);
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
