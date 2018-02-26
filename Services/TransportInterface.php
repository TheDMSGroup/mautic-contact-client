<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Services;

/**
 * Interface TransportInterface.
 */
interface TransportInterface
{
    public function patch($uri, array $options);

    public function post($uri, array $options);

    public function put($uri, array $options);

    public function get($uri, array $options);

    public function head($uri, array $options);

    public function delete($uri, array $options);

    public function request(array $options);
}
