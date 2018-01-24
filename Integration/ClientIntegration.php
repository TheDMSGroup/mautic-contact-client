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
use MauticPlugin\MauticContactClientBundle\Entity\ContactClientRepository;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayload;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\Constraints\NotBlank;

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

    protected $container;

    public function getDisplayName()
    {
        return 'Clients';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'push_leads'];
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
     * Push a contact to a preconfigured Contact Client.
     *
     * @param Contact $contact
     * @param array $config
     * @return bool
     */
    public function pushLead($contact, $config = [])
    {
        $container = $this->dispatcher->getContainer();

        $config = $this->mergeConfigToFeatureSettings($config);
        if (empty($config['contactclient'])) {
            return false;
        }

        /** @var Contact $contactModel */
        $clientModel = $container->get('mautic.contactclient.model.contactclient');
        $client = $clientModel->getEntity($config['contactclient']);
        if (!$client || $client->getIsPublished() === false) {
            return false;
        }

        // Get field overrides.
        $overrides = [];
        if (!empty($config['contactclient_overrides'])) {
            // Flatten overrides to key-value pairs.
            $obj = json_decode($config['contactclient_overrides']);
            $overrides = [];
            if ($obj) {
                foreach ($obj as $field) {
                    if (!empty($field->key) && !empty($field->value)) {
                        $overrides[$field->key] = $field->value;
                    }
                }
            }
        }


        $result = false;
        try {
            $result = $this->sendContact($client, $contact, $container, false, $overrides);
        } catch (\Exception $e) {
            if ($e instanceof ApiErrorException) {
                $e->setContact($contact);
            }
            $this->logIntegrationError($e);
        }

        return $result;
    }

    /**
     * Merges a config from integration_list with feature settings.
     *
     * @param array $config
     *
     * @return array|mixed
     */
    public function mergeConfigToFeatureSettings($config = [])
    {
        $featureSettings = $this->settings->getFeatureSettings();

        if (isset($config['config'])
            && (empty($config['integration'])
                || (!empty($config['integration'])
                    && $config['integration'] == $this->getName()))
        ) {
            $featureSettings = array_merge($featureSettings, $config['config']);
        }

        return $featureSettings;
    }

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
     * Given the JSON API API instructions payload instruction set.
     * Send the lead/contact to the API by following the steps.
     *
     * @param ContactClient $client
     * @param Contact $contact
     * @param Container $container
     * @param bool $test
     * @param array $overrides
     * @return bool
     * @throws ApiErrorException
     * @throws Exception
     */
    public function sendContact(
        ContactClient $client,
        Contact $contact,
        Container $container,
        $test = false,
        $overrides = []
    ) {

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

        if ($overrides) {
            $this->payload->setOverrides($overrides);
        }

        try {
            $this->valid = $this->payload->run();
        } catch (ApiErrorException $e) {
            // Failure to validate one or more API operations in the payload.
            $this->valid = false;
            $this->logs[] = $e->getMessage();
            $this->logIntegrationError($e, $this->contact);
        }
        $this->logs = array_merge($this->payload->getLogs(), $this->logs);

        $this->updateContact();

        $this->logResults();

        return $this->valid;
    }

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
                    $this->logIntegrationError($e, $this->contact);
                }
            } else {
                $this->logs[] = 'Operation successful, but no fields on the Contact needed updating.';
            }
        }
    }

    /**
     * Map the response to contact fields and update the contact, logging the action.
     *
     * @param array $response Expected response mapping.
     */

    private function logResults()
    {
        // @todo - Ensure audit log, stats log, and lead logs on success.
        // Do something with $this->logs.
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function pushLeads($params = [])
    {
        $limit = (isset($params['limit'])) ? $params['limit'] : 100;

        // @todo - Push multiple contacts by Campaign Action.

//        list($fromDate, $toDate) = $this->getSyncTimeframeDates($params);
//        $config                  = $this->mergeConfigToFeatureSettings($params);
//        $integrationEntityRepo   = $this->getIntegrationEntityRepository();
        $totalUpdated = 0;
        $totalCreated = 0;
        $totalErrors = 0;
        $totalIgnored = 0;

        return [$totalUpdated, $totalCreated, $totalErrors, $totalIgnored];
    }

    public function getValid()
    {
        return $this->valid;
    }

    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|\Symfony\Component\Form\FormBuilder $builder
     * @param array $data
     * @param string $formArea
     * @throws ApiErrorException
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'integration') {
            if ($this->isAuthorized()) {

                // @todo - Remove use of deprecated factory when a better way to get the container exists.
                // $container = $this->dispatcher->getContainer();
                $clientModel = $this->factory->get('mautic.contactclient.model.contactclient');
                /** @var contactClientRepository $contactClientRepo */
                $contactClientRepo = $clientModel->getRepository();
                $contactClientEntities = $contactClientRepo->getEntities();
                $clients = ['' => ''];
                $overridableFields = [];
                foreach ($contactClientEntities as $contactClientEntity) {
                    if ($contactClientEntity->getIsPublished()) {
                        $id = $contactClientEntity->getId();
                        $clients[$id] = $contactClientEntity->getName();

                        // Get overridable fields from the payload of the type needed.
                        if ($contactClientEntity->getType() == 'api') {
                            $payload = new ApiPayload($contactClientEntity);
                            $overridableFields[$id] = $payload->getOverridableFields();
                        } else {
                            // @todo - File based payload.
                        }
                    }
                }
                if (count($clients) === 1) {
                    $clients = ['', '-- No Clients have been created and published --'];
                }

                $builder->add(
                    'contactclient',
                    'choice',
                    [
                        'choices' => $clients,
                        'expanded' => false,
                        'label_attr' => ['class' => 'control-label'],
                        'multiple' => false,
                        'label' => 'mautic.contactclient.integration.client',
                        'attr' => [
                            'class' => 'form-control',
                            'tooltip' => 'mautic.contactclient.integration.client.tooltip',
                            // Auto-set the integration name based on the client.
                            'onchange' =>
                                "var client = mQuery('#campaignevent_properties_config_contactclient:first'),".
                                "    eventName = mQuery('#campaignevent_name');".
                                "if (client.length && client.val() && eventName.length) {".
                                "    eventName.val(client.text().trim());".
                                "}",
                        ],
                        'required' => true,
                        'constraints' => [
                            new NotBlank(
                                ['message' => 'mautic.core.value.required']
                            ),
                        ],
                        'choice_attr' => function ($val, $key, $index) use ($overridableFields) {
                            $results = [];
                            // adds a class like attending_yes, attending_no, etc
                            if ($val && isset($overridableFields[$val])) {
                                $results['class'] = 'contact-client-'.$val;
                                // Change format to match json schema.
                                $results['data-overridable-fields'] = json_encode($overridableFields[$val]);
                            }

                            return $results;
                        },
                    ]
                );

                $builder->add(
                    'contactclient_overrides_button',
                    'button',
                    [
                        'label' => 'mautic.contactclient.integration.overrides',
                        'attr' => [
                            'class' => 'btn btn-default btn-nospin',
                            'tooltip' => 'mautic.contactclient.integration.overrides.tooltip',
                            // Shim to get our javascript over the border and into Integration land.
                            'onclick' =>
                                "if (typeof Mautic.contactclientIntegration === 'undefined') {".
                                "    mQuery.getScript(mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/build/contactclient.min.js', function(){".
                                "        Mautic.contactclientIntegration();".
                                "    });".
                                "    mQuery('head').append('<"."link rel=\'stylesheet\' href=\'' + mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/build/contactclient.min.css\' type=\'text/css\' />');".
                                "} else {".
                                "    Mautic.contactclientIntegration();".
                                "}",
                            'icon' => 'fa fa-wrench',
                        ],
                    ]
                );

                $builder->add(
                    'contactclient_overrides',
                    'textarea',
                    [
                        'label' => 'mautic.contactclient.integration.overrides',
                        'label_attr' => ['class' => 'control-label hide'],
                        'attr' => [
                            'class' => 'form-control hide',
                            'tooltip' => 'mautic.contactclient.integration.overrides.tooltip',
                        ],
                        'required' => false,
                    ]
                );
            }
        }
    }
}
