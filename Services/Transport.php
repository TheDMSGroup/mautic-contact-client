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

use GuzzleHttp\Client;

class Transport implements TransportInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * TransportService constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function post($uri, array $options = [])
    {
        return $this->client->request('POST', $uri, $options);
    }

    public function put($uri, array $options = [])
    {
        return $this->client->request('PUT', $uri, $options);
    }

    public function get($uri, array $options = [])
    {
        return $this->client->request('GET', $uri, $options);
    }

    public function delete($uri, array $options = [])
    {
        return $this->client->request('DELETE', $uri, $options);
    }

    public function request(array $options = [])
    {
        return $this->client->request($options);
    }
}
