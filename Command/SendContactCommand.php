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
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticContactClientBundle\Integration\ClientIntegration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command : Sends a contact to a client/queue.
 *
 * php app/console mautic:contactclient:sendcontact [--client=%clientId% [--contact=%contactId%] [--test]]
 */
class SendContactCommand extends ModeratedCommand
{
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('mautic:contactclient:sendcontact')
            ->setDescription('Sends a contact to a client/queue.')
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
        $options    = $input->getOptions();
        $container  = $this->getContainer();
        $translator = $container->get('translator');

        if (!$this->checkRunStatus($input, $output, $options['client'].$options['contact'])) {
            return 0;
        }

        if (!$options['client'] || !is_numeric($options['client'])) {
            $output->writeln('<error>'. $translator->trans('mautic.contactclient.sendcontact.error.client').'</error>');

            return 0;
        }

        if (!$options['contact'] || !is_numeric($options['contact'])) {
            $output->writeln('<error>'. $translator->trans('mautic.contactclient.sendcontact.error.contact').'</error>');

            return 0;
        }

        $clientModel = $container->get('mautic.contactclient.model.contactclient');
        $client      = $clientModel->getEntity($options['client']);
        if (!$client) {
            $output->writeln('<error>'.$translator->trans('mautic.contactclient.sendcontact.error.client.load').'</error>');

            return 0;
        }

        if (false === $client->getIsPublished() && !$options['force']) {
            $output->writeln('<error>'. $translator->trans('mautic.contactclient.sendcontact.error.client.publish').' .</error>');

            return 0;
        }

        /** @var \Mautic\LeadBundle\Model\LeadModel $contactModel */
        $contactModel = $container->get('mautic.lead.model.lead');
        /** @var \Mautic\LeadBundle\Entity\Lead $contact */
        $contact = $contactModel->getEntity($options['contact']);
        if (!$contact) {
            $output->writeln('<error>'.$translator->trans('mautic.contactclient.sendcontact.error.contact.load').'</error>');

            return 0;
        }

        if (in_array($client->getType(), ['api', 'file'])) {
            // Load the integration helper for our general ClientIntegration
            /** @var IntegrationHelper $integrationHelper */
            $integrationHelper = $container->get('mautic.helper.integration');
            /** @var ClientIntegration $integrationObject */
            $integrationObject = $integrationHelper->getIntegrationObject('Client');
            if (
                !$integrationObject
                || (false === $integrationObject->getIntegrationSettings()->getIsPublished() && !$options['force'])
            ) {
                $output->writeln('<error>'.$translator->trans('mautic.contactclient.sendcontact.error.plugin.publish').'</error>');

                return 0;
            }
            $integrationObject->sendContact($client, $contact, $options['test']);
            if ($integrationObject->getValid()) {
                $output->writeln('<info>'.$translator->trans('mautic.contactclient.sendcontact.contact.accepted').'</info>');
                if (isset($options['verbose']) && $options['verbose']) {
                    $output->writeln('<info>'.$integrationObject->getLogsYAML().'</info>');
                }
            } else {
                $output->writeln('<error>'.$translator->trans('mautic.contactclient.sendcontact.contact.rejected').'</error>');
                if (isset($options['verbose']) && $options['verbose']) {
                    $output->writeln('<info>'.$integrationObject->getLogsYAML().'</info>');
                }
            }
        } else {
            $output->writeln('<error>'.$translator->trans('mautic.contactclient.sendcontact.client.type').'</error>');

            return 0;
        }

        $this->completeRun();

        return 0;
    }
}
