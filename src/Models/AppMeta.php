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
    use SoftDeletes;
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

}
?>
