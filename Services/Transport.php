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

/**
 * Class Transport.
 */
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
     * Default Guzzle options excluding options we'll likely incorporate in the future:
     *  body
     *  cert
     *  ssl_key
     *    form_params
     *    json
     *    multipart
     *    query
     *  proxy.
     *
     * @var array default options for transportation
     */
    private $settings = [
        'allow_redirects' => [
            'max'             => 10,
            'strict'          => false,
            'referer'         => false,
            'protocols'       => ['https', 'http'],
            'track_redirects' => true,
        ],
        'connect_timeout' => 10,
        'cookies'         => true,
        'http_errors'     => false,
        'synchronous'     => true,
        'verify'          => false,
        'timeout'         => 30,
        'version'         => 1.1,
        'headers'         => null,
    ];

    /**
     * Transport constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = new $client($this->settings);
    }

    /**
     * Establish a new Transport with the options provided if needed.
     * Only options that match the keys of our defaults are to be supported.
     *
     * @param array $settings
     */
    public function setSettings($settings = [])
    {
        $original = $this->settings;
        $this->mergeSettings($settings, $this->settings);
        if ($this->settings != $original) {
            $this->client = new Client($this->settings);
        }
    }

    /**
     * Merge settings from an external source overriding internals by a nested array.
     *
     * @param array $settingsa
     * @param array $settingsb
     */
    private function mergeSettings($settingsa, &$settingsb)
    {
        foreach ($settingsb as $key => &$value) {
            if (isset($settingsa[$key])) {
                if (is_array($value)) {
                    $this->mergeSettings($settingsa[$key], $value);
                } else {
                    $value = $settingsa[$key];
                }
            }
        }
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

    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    public function getBody()
    {
        return $this->response->getBody();
    }

    public function getHeaders()
    {
        return $this->response->getHeaders();
    }
}
