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
class ModelMeta extends Model {

    use MetaTrait;
    use TimeDiff;

    protected $table = "model_metas";

    protected $fillable = [
        'model_id', 'model_type',
        'meta_key','meta_value', 'meta_type', 'data', 'category'
    ];
    
    protected $casts = [
        'data' => 'array'
    ];

    /**
     * Get the parent model
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function modellable()
    {
        return $this->morphTo(__FUNCTION__, 'model_type', 'model_id');
    }

    /**
     * Get key prefix
     *
     * @return string
     */
    public static function getKeyPrefix(): string {
        return "model_meta_";
    }


}
?>
