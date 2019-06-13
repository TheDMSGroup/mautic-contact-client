<?php

namespace MauticPlugin\MauticContactClient\Tests\Model;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Mautic\CoreBundle\Test\MauticSqliteTestCase;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Services\Transport;

class ApiPayloadTest extends MauticSqliteTestCase
{
    public function testTestApiSend()
    {
        $container = [];
        $history   = Middleware::history($container);
        $stack     = HandlerStack::create();
        $stack->push($history);
        $guzzle = new Client(['handler' => $stack]);
        $this->container->set('mautic.contactclient.guzzle.client', $guzzle);
        $this->container->set('mautic.contactclient.service.transport', new Transport($guzzle));

        /** @var ApiPayload $apiPayload */
        $apiPayload = $this->container->get('mautic.contactclient.model.apipayload');

        $client = new ContactClient();
        $client->setType('api');
        $client->setAPIPayload($this->getPayload());

        $apiPayload->setTest(true);
        $apiPayload->setContactClient($client);
        /* $apiPayload->setEvent(); */

        $apiPayload->run();

        var_dump($container);
    }

    private function getPayload()
    {
        $reflection = new \ReflectionClass($this);

        $dir =  explode('/', $reflection->getFilename());
        array_pop($dir);
        $dir = implode('/', $dir);
        // Looks good to me, ship it!
        $dir .= '/../assets/model/api_payload.json';
        $payload = file_get_contents($dir);

        return $payload;
    }
}
