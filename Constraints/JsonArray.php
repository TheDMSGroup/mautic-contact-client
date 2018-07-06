<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class JsonArray.
 */
class JsonArray extends Constraint
{
    const IS_JSON_ARRAY_ERROR = 'daa9e1bc-bd0e-4197-9e18-e29ed414824c';

    /** @var array */
    protected static $errorNames = [
        self::IS_JSON_ARRAY_ERROR => 'IS_JSON_ARRAY_ERROR',
    ];

    /** @var string */
    public $message = 'This value should be empty or contain a JSON array.';
}
