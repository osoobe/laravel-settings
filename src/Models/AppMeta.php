<?php

namespace Osoobe\Laravel\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Osoobe\Laravel\Settings\Traits\MetaTrait;
use Osoobe\Utilities\Helpers\Utilities;
use Osoobe\Utilities\Traits\TimeDiff;

/**
 * Settings for laravel application.
 *
 * @property string $meta_key       Application setting key.
 * @property mixed $meta_value      Application setting value.
 */
class AppMeta extends Model {

    use MetaTrait;
    use TimeDiff;

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
        $version = version('short');
        $hostnames = static::getOrCreateMeta('app.hostnames', [
            "$version" => [
                Utilities::getHostnameHash()."" => "0.0.0.0"
            ]
        ]);

        $version_hostnames = Utilities::getArrayValue($hostnames, $version);

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
        $version = version('short');
        $hostnames = static::getVersionHostnames();
        if ( ! is_array($hostnames) ) {
            $hostnames = [];
        }

        if ( !empty($hostnames[$version]) ) {
            return [];
        }

        $hostnames[Utilities::getHostnameHash().""] = config('app.service', 'app');
        static::updateMeta('app.hostnames', [
            "$version" => $hostnames
        ]);
        return $hostnames;
    }

}
?>
