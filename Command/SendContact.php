<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
// use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command : Sends a contact to a client.
 *
 * php app/console mautic:contactclient:send [--client=%clientId% [--id=%contactId%]]
 */
class SendContact extends ModeratedCommand
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
                'id',
                'i',
                InputOption::VALUE_REQUIRED,
                'The id of a contact to send.',
                null
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $model   = $this->getContainer()->get('mautic.contactclient.model.contactclient');
        $options = $input->getOptions();
        $client = $options['client'];
        $contact = $options['id'];

        if (!$this->checkRunStatus($input, $output, $options['client'].$options['id'])) {
            return 0;
        }

        $output->writeln('Contact not sent, this method is still a stub.');
        $output->writeln('<info>Done.</info>');

        $this->completeRun();
    }
}
