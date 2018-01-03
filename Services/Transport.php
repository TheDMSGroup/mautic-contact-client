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
     * @var
     */
    private $response;

    /**
     * Default Guzzle configuration excluding options we'll likely incorporate in the future:
     *  body
     *  cert
     *  ssl_key
     *    form_params
     *    json
     *    multipart
     *    query
     *  proxy
     * @todo - Allow only pertinent options to be overridden by the API Payload.
     * @var array Default options for transportation.
     */
    private $config = [
        'allow_redirects' => [
            'max' => 10,
            'strict' => false,
            'referer' => false,
            'protocols' => ['https', 'http'],
            'track_redirects' => true,
        ],
        'connect_timeout' => 10,
        'cookie' => true,
        'http_errors' => false,
        'synchronous' => true,
        'verify' => true,
        'timeout' => 60,
        'version' => 1.1,
        'headers' => null,
    ];

    /**
     * Transport constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = new $client($this->config);
    }

    public function patch($uri, array $options = [])
    {
        $this->response = $this->client->request('PATCH', $uri, $options);
        return $this->response;
    }

    public function post($uri, array $options = [])
    {
        $this->response = $this->client->request('POST', $uri, $options);
        return $this->response;
    }

    public function put($uri, array $options = [])
    {
        $this->response = $this->client->request('PUT', $uri, $options);
        return $this->response;
    }

    public function get($uri, array $options = [])
    {
        $this->response = $this->client->request('GET', $uri, $options);
        return $this->response;
    }

    public function head($uri, array $options = [])
    {
        $this->response = $this->client->request('HEAD', $uri, $options);
        return $this->response;
    }

    public function delete($uri, array $options = [])
    {
        $this->response = $this->client->request('DELETE', $uri, $options);
        return $this->response;
    }

    public function request(array $options = [])
    {
        $this->response = $this->client->request($options);
        return $this->response;
    }

    public function getStatusCode() {
        return $this->response->getStatusCode();
    }

    public function getBody() {
        return $this->response->getBody();
    }
}
