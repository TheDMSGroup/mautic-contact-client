<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Services;

interface TransportInterface
{
    public function post($uri, array $options);

    public function put($uri, array $options);

    public function get($uri, array $options);

    public function delete($uri, array $options);

    public function request(array $options);
}
