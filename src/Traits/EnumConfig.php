<?php


namespace Osoobe\Laravel\Settings\Traits;

use Osoobe\Laravel\Settings\Models\AppMeta;


trait EnumConfig {


    /**
     * Get the config for the settings
     *
     * @param mixed $default
     * @return mixed
     */
    public function config($default=null) {
        return AppMeta::config($this->value, $default);
    }

    /**
     * Set config
     *
     * @param mixed $value
     * @return void
     */
    public function setConfig($value) {
        AppMeta::setConfig($this->value, $value);
    }



    /**
     * Get the config secret for the settings
     *
     * @param mixed $default
     * @return mixed
     */
    public function secret($default=null) {
        return AppMeta::secret($this->value, $default);
    }

    /**
     * Set config secret
     *
     * @param mixed $value
     * @return void
     */
    public function setSecret($value) {
        AppMeta::setSecret($this->value, $value);
    }

}

?>
