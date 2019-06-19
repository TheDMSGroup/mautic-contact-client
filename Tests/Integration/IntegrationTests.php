<?php

namespace MauticPlugin\MauticContactClientBundle\Tests\Integration;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Integration\ClientIntegration;
use MauticPlugin\MauticContactClientBundle\Tests\ContactClientTestCase;

class IntegrationTests extends ContactClientTestCase
{
    public function testBasicPushLead()
    {
        $client  = $this->getClientEntity(1);
        $contact = $this->getContact();

        /** @var IntegrationHelper $integrationHelper */
        $integrationHelper = $this->container->get('mautic.helper.integration');
        /** @var ClientIntegration $integrationObject */
        $integrationObject = $integrationHelper->getIntegrationObject('Client');

        $results = $integrationObject->sendContact($client, $contact);
        $this->assertEquals(
            Stat::TYPE_CONVERTED,
            $results->getStatType(),
            'Stat Type "Converted" Expected. Received '.$results->getStatType()
        );
    }

    public function testFieldsPushLead()
    {
        $client  = $this->getClientEntity(2);
        $contact = $this->getContact();

        /** @var IntegrationHelper $integrationHelper */
        $integrationHelper = $this->container->get('mautic.helper.integration');
        /** @var ClientIntegration $integrationObject */
        $integrationObject = $integrationHelper->getIntegrationObject('Client');

        $results = $integrationObject->sendContact($client, $contact);
        $this->assertEquals(
            Stat::TYPE_FIELDS,
            $results->getStatType(),
            'Stat Type "Fields" Expected. Received '.$results->getStatType()
        );
    }

    public function testOffSchedulePushLead()
    {
        $client  = $this->getClientEntity(3);
        $contact = $this->getContact();

        /** @var IntegrationHelper $integrationHelper */
        $integrationHelper = $this->container->get('mautic.helper.integration');
        /** @var ClientIntegration $integrationObject */
        $integrationObject = $integrationHelper->getIntegrationObject('Client');

        $results = $integrationObject->sendContact($client, $contact);
        $this->assertEquals(
            Stat::TYPE_SCHEDULE,
            $results->getStatType(),
            'Stat Type "Schedule" Expected. Received '.$results->getStatType()
        );
    }

    public function testDuplicatesPushLead()
    {
        $client  = $this->getClientEntity(4);
        $contact = $this->getContact();

        /** @var IntegrationHelper $integrationHelper */
        $integrationHelper = $this->container->get('mautic.helper.integration');
        /** @var ClientIntegration $integrationObject */
        $integrationObject = $integrationHelper->getIntegrationObject('Client');

        $results1 = $integrationObject->sendContact($client, $contact);
        $this->assertEquals(
            Stat::TYPE_CONVERTED,
            $results1->getStatType(),
            'Stat Type "Converted" Expected. Received '.$results1->getStatType()
        );
        $results2 = $integrationObject->sendContact($client, $contact);
        $this->assertEquals(
            Stat::TYPE_DUPLICATE,
            $results2->getStatType(),
            'Stat Type "Duplicate" Expected. Received '.$results2->getStatType()
        );
    }
}
