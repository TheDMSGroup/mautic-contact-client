<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class JsonObject
 */
class JsonObject extends Constraint
{
    const IS_JSON_OBJECT_ERROR = '43d9ba7a-375f-11e8-b467-0ed5f89f718b';

    /** @var array */
    protected static $errorNames = [
        self::IS_JSON_OBJECT_ERROR => 'IS_JSON_OBJECT_ERROR',
    ];

    /** @var string */
    public $message = 'This value should be empty or contain a JSON object.';
}
