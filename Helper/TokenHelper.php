<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Helper;

use Mustache_Engine as Engine;

/**
 * Class TokenHelper.
 */
class TokenHelper
{
    /**
     * To reduce overhead, fields will be searched for this before attempting token replacement.
     */
    const TOKEN_KEY = '{{';

    /**
     * @var Engine
     */
    private $engine;

    /**
     * @var array Context of tokens for replacement.
     */
    private $context = [];

    /**
     * TokenHelper constructor.
     * @param array $context
     */
    public function __construct($context = [])
    {
        $this->engine = new Engine();
        $this->setContext($context);
    }

    /**
     * Recursively replaces tokens using an array for context.
     * @param array $array
     * @return array
     */
    public function renderArray($array = [])
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (strpos($key, self::TOKEN_KEY) !== false) {
                $key = $this->engine->render($key, $this->context);
            }
            if (is_string($value)) {
                if (strpos($value, self::TOKEN_KEY) !== false) {
                    $value = $this->engine->render($value, $this->context);
                }
            } elseif (is_array($value) || is_object($value)) {
                $value = $this->tokenizeArray($value, $this->context);
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Replace Tokens in a simple string using an array for context.
     * @param $string
     * @return string
     */
    public function renderString($string) {
        if (strpos($string, self::TOKEN_KEY) !== false) {
            $string = $this->engine->render($string, $this->context);
        }
        return $string;
    }

    /**
     * @param array $context
     */
    public function setContext($context = []) {
        $this->context = $context;
    }
}
