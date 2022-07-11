<?php

namespace Osoobe\Laravel\Settings\Traits;

use Carbon\Carbon;
use Osoobe\Utilities\Helpers\Utilities;

trait History {


    public function metaTrack() {
        return [];
    }


    /**
     * Create Notification Settings.
     *
     * @todo create notification setting package.
     * @return void
     */
    protected static function bootHistory(): void {

        $func = function ($data, $trackers) {
            $trackers[] = 'updated_at';
            foreach(array_keys($data) as $key) {
                if ( !in_array($key, $trackers) ) {
                    unset($data[$key]);
                }
            }
            return $data;
        };

        static::created(function($model) use($func) {
            $data = $model->getRawOriginal();
            $trackers = $model->metaTrack();
            $changes = $func($data, $trackers);
            $changes['history_status'] = "created";
            $model->updateMetaHistory($changes, true);
        });

        static::updated(function($model) use($func) {
            $data = $model->getRawOriginal();
            $trackers = $model->metaTrack();
            $changes = $func($data, $trackers);
            $changes['history_status'] = "updated";
            $model->updateMetaHistory($changes, false);
        });
    }

    public function getMetaHistory($cache = true) {
        return $this->getMetaValue('history', $cache);
    }

    public function updateMetaHistory(array $changes = [], $purge=false) {
        $history = $this->getMetaHistory(false);
        if ( empty($history) || $purge ) {
            $history = [];
        }

        $history_status = Utilities::getArrayValue($changes, 'history_status', 'updated');

        $len = count($history);
        if ( $len >= 1 ) {
            $changes = array_diff($changes, $history[$len - 1]);
        }

        if ( isset($this->updated_at) ) {
            $changes['updated_at'] = $this->getRawOriginal('updated_at');
        }
        $changes['timestamp'] = Carbon::now()->timestamp;
        $changes['history_status'] = $history_status;
        $history[] = $changes;
        return $this->updateMeta('history', $history);
    }

}

?>
