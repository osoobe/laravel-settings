<?php

namespace Osoobe\Laravel\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Osoobe\Laravel\Settings\Traits\MetaTrait;

/**
 * Settings for laravel application.
 *
 * @property string $meta_key       Application setting key.
 * @property mixed $meta_value      Application setting value.
 */
class AppMeta extends Model {

    use MetaTrait;

    protected $table = "app_metas";
    const CATEGORY = "default";

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
        return static::getMetaByCategory();
    }



    /**
     * Get hostnames for the current version of the app
     *
     * @return array|mixed
     */
    public static function getVersionHostnames() {
        $version = config('app.version', '1.0');
        $hostname = gethostname() ?: 'default';
        $hostnames = static::getOrCreateMeta('app.hostnames', [
            "$version" => [
                $hostname => "0.0.0.0"
            ]
        ]);

        $version_hostnames = !empty($hostnames[$version]) ? $hostnames[$version] : null;

        if ( ! is_array($version_hostnames) ) {
            return [];
        }
        return $version_hostnames;
    }


    /**
     * Append hostname to the current version of the app
     *
     * @return array
     */
    public static function appendVersionHostname() {
        $version = config('app.version', '1.0');
        $hostnames = static::getVersionHostnames();
        if ( ! is_array($hostnames) ) {
            $hostnames = [];
        }

        if ( !empty($hostnames[$version]) ) {
            return [];
        }

        $hostname = gethostname() ?: 'default';
        $hostnames[$hostname] = config('app.service', 'app');
        static::updateMeta('app.hostnames', [
            "$version" => $hostnames
        ]);
        return $hostnames;
    }

}
?>
