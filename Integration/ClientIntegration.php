<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Integration;

use Exception;
use Mautic\LeadBundle\Entity\Lead as Contact;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayload;
use Symfony\Component\DependencyInjection\Container;
//use MauticPlugin\MauticContactClientBundle\Helper\ContactEventLogHelper;

/**
 * Class ContactClientIntegration.
 *
 * @todo - Rename to ClientAPIIntegration to make room for ClientFileIntegration, with virtually no overlap.
 */
class ClientIntegration extends AbstractIntegration
{

    /**
     * @var ContactClient client we are about to send this Contact to.
     */
    protected $client;

    /**
     * @var array Of temporary log entries.
     */
    protected $logs = [];

    /**
     * @var Lead The contact we wish to send and update.
     */
    protected $contact;

    /**
     * @var bool Test mode.
     */
    protected $test = false;

    protected $payload;


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Client';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * Given the JSON API API instructions payload instruction set.
     * Send the lead/contact to the API by following the steps.
     *
     * @param ContactClient $client
     * @param Contact $contact
     * @param Container $container
     * @param bool $test
     * @throws ApiErrorException
     * @throws Exception
     */
    public function sendContact(ContactClient $client, Contact $contact, Container $container, $test = false)
    {
        $this->test = $test;
        // @todo - add translation layer for strings in this method.
        // $translator = $container->get('translator');

        if (!$client) {
            throw new ApiErrorException('Contact Client appears to not exist.');
        }
        $this->client = $client;

        if (!$contact) {
            throw new ApiErrorException('Contact appears to not exist.');
        }
        $this->contact = $contact;

        $this->payload = new ApiPayload($client, $contact, $container, $test);
        try {
            $this->payload->run();
        } catch (ApiErrorException $e) {
            $this->logs[] = $e->getMessage();
        }
        $this->logs['payload'] = $this->payload->getLogs();

        $this->logResults();
        die(var_dump($this->logs));
    }

    /**
     * Map the response to contact fields and update the contact, logging the action.
     *
     * @param array $response Expected response mapping.
     */
    private function updateContact($response = [])
    {
//        if (!$this->isAborted()) {
//
//            // Check the response against the expected response for any field mappings.
//            foreach (['headers', 'body'] as $type) {
//                if (
//                    isset($this->response[$type])
//                    && is_array($this->response[$type])
//                    && count($this->response[$type])
//                    && isset($response[$type])
//                    && is_array($response[$type])
//                    && count($response[$type])
//                ) {
//                    // We have found an input type with a result that was expected.
//                    // @todo - Update the values on the contact.
//                }
//            }
//
//            // @todo - If we find field values to map, update the contact and save.
//
//            // @todo - Log an event on the contact to           where this update came from.
//        }
    }

    private function logResults()
    {

    }


}
