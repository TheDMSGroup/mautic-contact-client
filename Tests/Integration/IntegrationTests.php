<?php

namespace MauticPlugin\MauticContactClientBundle\Bundle\Tests\Integration;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticContactClientBundle\Bundle\Tests\ContactClientTestCase;
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

        $integrationObject->sendContact($client, $contact);
    }
}