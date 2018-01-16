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
     * @var Contact The contact we wish to send and update.
     */
    protected $contact;

    /**
     * @var bool Test mode.
     */
    protected $test = false;

    protected $payload;

    protected $valid = true;

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
        // @todo - add translation layer for strings in this method.
        // $translator = $container->get('translator');

        $this->container = $container;
        $this->test = $test;

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
            $this->valid = $this->payload->run();
        } catch (ApiErrorException $e) {
            $this->valid = false;
            $this->logs[] = $e->getMessage();
        }
        $this->logs = array_merge($this->payload->getLogs(), $this->logs);

        $this->updateContact();

        $this->logResults();
        die(var_dump($this->logs));
    }

    /**
     * Map the response to contact fields and update the contact, logging the action.
     *
     * @param array $response Expected response mapping.
     */
    /**
     * Loop through the API Operation responses and find valid field mappings.
     * Set the new values to the contact and log the changes thereof.
     */
    private function updateContact()
    {
        $responseMap = $this->payload->getResponseMap();
        if ($this->valid) {
            $updated = false;
            if (count($responseMap)) {
                foreach ($responseMap as $alias => $value) {
                    $oldValue = $this->contact->getFieldValue($alias);
                    if ($oldValue !== $value) {
                        $this->contact->addUpdatedField($alias, $value, $oldValue);
                        $this->logs[] = 'Updating Contact: '.$alias.' = '.$value;
                        $updated = true;
                    }
                }
            }
            if ($updated) {
                try {
                    $contactModel = $this->container->get('mautic.lead.model.lead');
                    $contactModel->saveEntity($this->contact);
                    $this->logs[] = 'Operation successful. The Contact was updated.';
                } catch (Exception $e) {
                    $this->logs[] = 'Failure to update our Contact. '.$e->getMessage();
                    $this->valid = false;
                }
            } else {
                $this->logs[] = 'Operation successful, but no fields on the Contact needed updating.';
            }
        }
    }

    private function logResults()
    {

    }
}
