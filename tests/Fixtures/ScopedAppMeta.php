<?php

namespace Osoobe\Laravel\Settings\Tests\Fixtures;

use Osoobe\Laravel\Settings\Models\AppMeta;

/**
 * AppMeta subclass for testing category scoping.
 *
 * Must override getDefaultCategory() because bootMetaTrait uses self::, which
 * resolves to AppMeta (where MetaTrait was compiled). Overriding the method
 * in this subclass is not picked up by self::; however, because Laravel calls
 * bootMetaTrait via forward_static_call with static set to this class,
 * using static:: inside bootMetaTrait would fix it. We work around the
 * current self:: limitation by overriding getDefaultCategory() here AND
 * accepting that category scoping requires the fix in MetaTrait.
 * The fixture is kept so scoping tests document the expected correct behaviour.
 */
class ScopedAppMeta extends AppMeta
{
    const CATEGORY = 'scoped';

    public static function getDefaultCategory(): string
    {
        return static::CATEGORY;
    }

    public static function getKeyPrefix(): string
    {
        return 'scoped_meta_';
    }
}
