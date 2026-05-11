<?php

namespace Osoobe\Laravel\Settings\Tests\Fixtures;

use Osoobe\Laravel\Settings\Traits\EnumConfig;

enum AppSettingsEnum: string
{
    use EnumConfig;

    case SiteName = 'app.name';
    case ApiKey   = 'services.api.key';
    case MaxItems = 'app.max_items';
}
