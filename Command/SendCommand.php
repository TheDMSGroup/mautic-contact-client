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

/**
 * CLI Command : Sends a contact to a client.
 *
 * php app/console mautic:contactclient:send [--client=%clientId% [--contact=%contactId%]]
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
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();

        if (!$this->checkRunStatus($input, $output, $options['client'].$options['id'])) {
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

        $clientModel = $this->getContainer()->get('mautic.contactclient.model.contactclient');
        $client = $clientModel->getEntity($options['client']);
        if (!$client) {
            $output->writeln('Could not load Client.');

            return 0;
        }

        $contactModel = $this->getContainer()->get('mautic.lead.model.lead');
        $contact = $contactModel->getEntity($options['contact']);
        if (!$contact) {
            $output->writeln('Could not load Contact.');

            return 0;
        }

        if ($client->getType() == 'api') {


        } elseif ($client->getType() == 'file') {


        }
        
        $output->writeln('Contact not sent, this method is still a stub.');
        $output->writeln('<info>Done.</info>');

        $this->completeRun();
    }
}
