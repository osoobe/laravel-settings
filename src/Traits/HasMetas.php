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

}

?>