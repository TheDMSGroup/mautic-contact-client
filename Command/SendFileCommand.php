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
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use MauticPlugin\MauticContactClientBundle\Model\FilePayload;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command : Sends queued file to a client.
 *
 * php app/console mautic:contactclient:sendfile [--client=%clientId% [--test]]
 */
class SendFileCommand extends ModeratedCommand
{
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('mautic:contactclient:sendfile')
            ->setDescription('Sends a contact to a client/queue.')
            ->addOption(
                'client',
                'c',
                InputOption::VALUE_OPTIONAL,
                'The contact client/s to send a file for. Otherwise all appropriate clients will be sent to.',
                null
            )
            ->addOption(
                'file',
                'l',
                InputOption::VALUE_OPTIONAL,
                'The id of a file to send. Otherwise a pending file will be used.',
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
     * Send appropriate client files.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $options   = $input->getOptions();
        /** @var ContactClientModel $clientModel */
        $clientModel = $container->get('mautic.contactclient.model.contactclient');
        /** @var FilePayload $payloadModel */
        $payloadModel = $container->get('mautic.contactclient.model.filepayload');
        $em           = $container->get('doctrine')->getManager();
        $translator   = $container->get('translator');

        // @todo - add translation layer for strings in this method.
        // $translator = $container->get('translator');

        if (!$this->checkRunStatus($input, $output, $options['client'].$options['file'])) {
            return 0;
        }

        $filter = [];
        if (!empty($options['client'])) {
            $filter['force'][] = [
                'column' => 'f.id',
                'expr'   => 'in',
                'value'  => explode(',', $options['client']),
            ];
        }
        $filter['where'][] = [
            'column' => 'f.type',
            'expr'   => 'eq',
            'value'  => 'file',
        ];
        $filter['where'][] = [
            'col'  => 'f.isPublished',
            'expr' => 'eq',
            'val'  => 1,
        ];
        $clients           = $clientModel->getEntities(
            [
                'filter'        => $filter,
                'iterator_mode' => true,
            ]
        );

        while (($client = $clients->next()) !== false) {
            $client = reset($client);
            // The client must still be published, and must still be set to send files.
            if (
                true === $client->getIsPublished()
                && 'file' == $client->getType()
            ) {

                // @todo - Update the translation here. Note, translation supports tokens, we'll need to refactor budgets to match.
                $output->writeln(
                    '<info>'.$translator->trans(
                        'mautic.contactclient.file.building',
                        ['%client%' => $client->getId()]
                    ).'</info>'
                );
                try {
                    $payloadModel->reset()
                        ->setContactClient($client)
                        ->setTest($options['test'])
                        ->getFileToBuild(false)
                        ->updateFileSettings()
                        ->buildFile()
                        ->sendFile();

                } catch (\Exception $e) {
                    // @todo - error handling.
                    $output->writeln(
                        '<warn>'.$translator->trans(
                            'mautic.contactclient.file.error',
                            ['%client%' => $client->getId(), '%message%' => $e->getMessage()]
                        ).'</warn>'
                    );

                    $tmp = 1;
                }
            }
            $em->detach($client);
            unset($client);
        }
        unset($clients);

        $this->completeRun();

        return 0;
    }
}
