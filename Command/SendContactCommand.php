<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Command;

use Mautic\ApiBundle\Model\ClientModel;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
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
                'contact-id',
                null,
                InputOption::VALUE_REQUIRED,
                'The id of a contact/lead to send.',
                null
            )
            ->addOption(
                'contact-ids',
                null,
                InputOption::VALUE_REQUIRED,
                'The ids of a contact/lead to send, comma separated',
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
            $output->writeln('<error>'.$translator->trans('mautic.contactclient.sendcontact.error.client').'</error>');

            return 1;
        }

        $contactIds = [];
        if (!empty($options['contact-ids'])) {
            $contactIds = explode(',', $options['contact-ids']);
            $contactIds = array_walk($contactIds, 'trim');
        } elseif (!empty($options['contact-id']) && is_numeric($options['contact-id'])) {
            $contactIds[] = (int) $options['contact-id'];
        }

        if (!$contactIds) {
            $output->writeln('<error>'.$translator->trans('mautic.contactclient.sendcontact.error.contact').'</error>');

            return 1;
        }

        /** @var ClientModel $clientModel */
        $clientModel = $container->get('mautic.contactclient.model.contactclient');
        /** @var ContactClient $client */
        $client = $clientModel->getEntity($options['client']);
        if (!$client) {
            $output->writeln(
                '<error>'.$translator->trans('mautic.contactclient.sendcontact.error.client.load').'</error>'
            );

            return 1;
        }

        if (false === $client->getIsPublished() && !$options['force']) {
            $output->writeln(
                '<error>'.$translator->trans('mautic.contactclient.sendcontact.error.client.publish').'</error>'
            );

            return 1;
        }

        // Load the integration helper for our general ClientIntegration
        /** @var IntegrationHelper $integrationHelper */
        $integrationHelper = $container->get('mautic.helper.integration');
        /** @var ClientIntegration $integrationObject */
        $integrationObject = $integrationHelper->getIntegrationObject('Client');
        if (
            !$integrationObject
            || (false === $integrationObject->getIntegrationSettings()->getIsPublished() && !$options['force'])
        ) {
            $output->writeln(
                '<error>'.$translator->trans('mautic.contactclient.sendcontact.error.plugin.publish').'</error>'
            );

            return 1;
        }

        /** @var \Mautic\LeadBundle\Model\LeadModel $contactModel */
        $contactModel = $container->get('mautic.lead.model.lead');
        foreach ($contactIds as $contactId) {
            /** @var \Mautic\LeadBundle\Entity\Lead $contact */
            $contact = $contactModel->getEntity($contactId);
            if (!$contact) {
                $output->writeln(
                    '<error>'.$translator->trans('mautic.contactclient.sendcontact.error.contact.load', ['%contactId%' => $contactId]).'</error>'
                );
            } else {
                $integrationObject->sendContact($client, $contact, (bool) $options['test'], (bool) $options['force']);
                if ($integrationObject->getValid()) {
                    $output->writeln(
                        '<info>'.$translator->trans('mautic.contactclient.sendcontact.contact.accepted', ['%contactId%' => $contactId]).'</info>'
                    );
                    if (isset($options['verbose']) && $options['verbose']) {
                        $output->writeln('<info>'.$integrationObject->getLogsYAML().'</info>');
                    }
                } else {
                    $output->writeln(
                        '<error>'.$translator->trans('mautic.contactclient.sendcontact.contact.rejected', ['%contactId%' => $contactId]).'</error>'
                    );
                    if (isset($options['verbose']) && $options['verbose']) {
                        $output->writeln('<info>'.$integrationObject->getLogsYAML().'</info>');
                    }
                }
            }
            $contactModel->clearEntities();
        }

        $this->completeRun();

        return 0;
    }
}
