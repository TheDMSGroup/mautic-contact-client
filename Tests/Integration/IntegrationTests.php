<?php

namespace MauticPlugin\MauticContactClientBundle\Tests\Integration;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticContactClientBundle\Tests\ContactClientTestCase;
use MauticPlugin\MauticContactClientBundle\Integration\ClientIntegration;

class IntegrationTests extends ContactClientTestCase
{
    public function testPushLead()
    {

        $client = $this->getClientEntity();
        $contact = $this->getContact();

        /** @var IntegrationHelper $integrationHelper */
        $integrationHelper = $this->container->get('mautic.helper.integration');
        /** @var ClientIntegration $integrationObject */
        $integrationObject = $integrationHelper->getIntegrationObject('Client');

        $results = $integrationObject->sendContact($client, $contact);
        $this->assertEquals(Stat::TYPE_CONVERTED, $results->getStatType(), 'Stat Type "Converted" Expected. Received ' . $results->getStatType() );
    }
}