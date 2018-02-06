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
//use MauticPlugin\MauticContactClientBundle\Entity\ContactClientRepository;
use MauticPlugin\MauticSocialBundle\Entity\Lead as Contact;

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
            $output->writeln('<error>Client is required.</error>');

            return 0;
        }

        if (!$options['contact'] || !is_numeric($options['contact'])) {
            $output->writeln('<error>Contact is required.</error>');

            return 0;
        }

        $clientModel = $container->get('mautic.contactclient.model.contactclient');
        $client = $clientModel->getEntity($options['client']);
        if (!$client) {
            $output->writeln('<error>Could not load Client.</error>');

            return 0;
        }

        if ($client->getIsPublished() === false && !$options['force']) {
            $output->writeln('<error>This client is not published. Publish it or use --force</error>');

            return 0;
        }

        /** @var Contact $contactModel */
        $contactModel = $container->get('mautic.lead.model.lead');
        $contact = $contactModel->getEntity($options['contact']);
        if (!$contact) {
            $output->writeln('<error>Could not load Contact.</error>');

            return 0;
        }

        $clientType = $client->getType();
        if ($clientType == 'api') {
            // Load the integration helper for our general ClientIntegration
            /** @var IntegrationHelper $integrationHelper */
            $integrationHelper = $container->get('mautic.helper.integration');
            /** @var ClientIntegration $integrationObject */
            $integrationObject = $integrationHelper->getIntegrationObject('Client');
            if (
                !$integrationObject
                || ($integrationObject->getIntegrationSettings()->getIsPublished() === false && !$options['force'])
            ) {
                $output->writeln('<error>The Contact Clients plugin is not published.</error>');

                return 0;
            }
            $integrationObject->sendContact($client, $contact, $options['test']);
            if ($integrationObject->getValid()) {
                $output->writeln('<info>Contact sent and accepted.</info>');
                if (isset($options['verbose']) && $options['verbose']) {
                    $output->writeln('<info>'.$integrationObject->getLogsYAML().'</info>');
                }
            } else {
                $output->writeln('<error>The Contact was not sent or accepted. See logs for details.</error>');
                $output->writeln('<warn>'.$integrationObject->getLogsYAML().'</warn>');
            }

        } elseif ($clientType == 'file') {

            // @todo - Support file payloads.

        } else {
            $output->writeln('<error>Client type is not recognized.</error>');

            return 0;
        }

        $this->completeRun();
    }
}
