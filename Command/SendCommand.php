<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;

//use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
//use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClientRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticContactClientBundle\Integration\ClientIntegration;

/**
 * CLI Command : Sends a contact to a client.
 *
 * php app/console mautic:contactclient:send [--client=%clientId% [--contact=%contactId%] [--test]]
 */
class SendCommand extends ModeratedCommand
{

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('mautic:contactclient:send')
            ->setDescription('Sends a contact to a client.')
            ->addOption(
                'client',
                'c',
                InputOption::VALUE_REQUIRED,
                'The contact client to send to.',
                null
            )
            ->addOption(
                'contact',
                'l',
                InputOption::VALUE_REQUIRED,
                'The id of a contact/lead to send.',
                null
            )
            ->addOption(
                'test',
                'i',
                InputOption::VALUE_NONE,
                'Run client requests in test mode.'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();
        $container = $this->getContainer();
        // @todo - add translation layer for strings in this method.
        // $translator = $container->get('translator');

        if (!$this->checkRunStatus($input, $output, $options['client'].$options['contact'])) {
            return 0;
        }

        if (!$options['client'] || !is_numeric($options['client'])) {
            $output->writeln('Client is required.');

            return 0;
        }

        if (!$options['contact'] || !is_numeric($options['contact'])) {
            $output->writeln('Contact is required.');

            return 0;
        }

        $clientModel = $container->get('mautic.contactclient.model.contactclient');
        $client = $clientModel->getEntity($options['client']);
        if (!$client) {
            $output->writeln('Could not load Client.');

            return 0;
        }

        if ($client->getIsPublished() === false && !$options['force']) {
            $output->writeln('This client is not published. Publish it or use --force');

            return 0;
        }

        $contactModel = $container->get('mautic.lead.model.lead');
        $contact = $contactModel->getEntity($options['contact']);
        if (!$contact) {
            $output->writeln('Could not load Contact.');

            return 0;
        }

        $clientType = $client->getType();
        if ($clientType == 'api') {
            // Load the integration helper for our general ClientIntegration
            $integrationHelper = $container->get('mautic.helper.integration');
            $integrationObject = $integrationHelper->getIntegrationObject('Client');
            if (!$integrationObject || ($integrationObject->getIntegrationSettings()->getIsPublished() === false && !$options['force'])) {
                $output->writeln('The Contact Clients plugin is not published.');

                return 0;
            }
            $integrationObject->setTestMode($options['test']);
            $integrationObject->sendContact($client->getApiPayload(), $client);

        } elseif ($clientType == 'file') {

        } else {
            $output->writeln('Client type is not recognized.');

            return 0;
        }

        $output->writeln('Contact not sent, this method is still a stub.');
        $output->writeln('<info>Done.</info>');

        $this->completeRun();
    }
}
