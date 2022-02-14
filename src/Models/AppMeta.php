<?php

namespace Osoobe\Laravel\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Osoobe\Utilities\Helpers\Utilities;
use Osoobe\Utilities\Traits\TimeDiff;

/**
 * Settings for laravel application.
 *
 * @property string $meta_key       Application setting key.
 * @property mixed $meta_value      Application setting value.
 */
class AppMeta extends Model {

    use SoftDeletes;
    use TimeDiff;

    protected $fillable = [
        'meta_key','meta_value', 'meta_type', 'data', 'category'
    ];

    protected $casts = [
        'data' => 'array'
    ];


    /**
     * Get application settings.
     *
     * @return mixed        Returns application settings.
     */
    public static function getAppSettingsByCategory() {
        $metas = AppMeta::orderBy('category', 'ASC')
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
                if (! is_string($val) && $type == "array" ) {
                    $val = json_encode($val);
                }
                static::updateOrCreate(
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
        if ( empty($type) ) {
            $type = gettype($val);
        }
<<<<<<< HEAD

        static::updateOrCreate(
            ['meta_key' =>  $key],
            [
                'meta_value' => ( $type == "array" )? json_encode($val) : $val,
                'meta_type' => $type
            ]
=======
        if ( ! is_string($val) && $type == "array" ) {
            $val = json_encode($val);
        }
        $model = static::updateOrCreate(
            ['meta_key' =>  $key],
            ['meta_value' => $val, 'meta_type' => $type, 'data' => $data]
>>>>>>> close: add boot
        );
        Cache::forever($cached_key, $val);
        return $model;
    }

    /**
     * Get key used by memcache.
     *
     * @param string $key          Application setting key
     * @return string
     */
    public static function getCachedKey(string $key): string {
        return "app_meta_$key";
    }


    /**
     * Model boot function
     *
     * @return void
     */
    public static function boot() {

        parent::boot();

        $adjust = function($model) {
            if ( empty($model->meta_type) ) {
                $model->meta_type = gettype($model->meta_value);
            }
            if ( ! is_string($model->meta_type) && $model->meta_type == "array" ) {
                $model->meta_value = json_encode($model->meta_value);
            }
        };

        static::creating($adjust);
        static::updating($adjust);
    }

}
?>
