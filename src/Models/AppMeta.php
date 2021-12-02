<?php

namespace Osoobe\Laravel\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Osoobe\Utilities\Helpers\Utilities;

/**
 * Settings for laravel application.
 *
 * @property string $meta_key       Application setting key.
 * @property mixed $meta_value      Application setting value.
 */
class AppMeta extends Model {
    use SoftDeletes;

    protected $fillable = [
        'meta_key','meta_value', 'category'
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
     * Get setting from cache or database.
     *
     * @param string $key          Application setting key
     * @param mixed $defaul        Default value to return.
     * @param mixed $type          Cast application setting value.
     * @return mixed               Returns the application setting value
     */
    public static function getMetaOrCached(string $key, $default=null, $type=null) {
        $val = $default;
        $cached_key = static::getCachedKey($key);
        if (Cache::has($cached_key)) {
            $val = Cache::get($cached_key);
        } else {
            $data = static::where('meta_key', $key)->first();
            if(!empty($data)) {
                $val = $data->meta_value;
            } else if ($default != null) {
                static::updateOrCreate(
                    ['meta_key' =>  $key],
                    ['meta_value' => $default]
                );
            }
            Cache::forever($cached_key, $val);
        }

        if ( $type != null && !empty($val) ) {
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
    public static function updateOrCreateWithCache(string $key, $val) {
        $cached_key = static::getCachedKey($key);
        static::updateOrCreate(
            ['meta_key' =>  $key],
            ['meta_value' => $val]
        );
        Cache::forever($cached_key, $val);
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

}
?>
