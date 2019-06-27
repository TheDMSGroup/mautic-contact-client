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

use Exception;
use Mautic\CoreBundle\Command\ModeratedCommand;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use MauticPlugin\MauticContactClientBundle\Model\FilePayload;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command : Sends queued file to a client.
 *
 * php app/console mautic:contactclient:sendfile [--client=%clientId%] [--test] [--mode=build|send|both]
 */
class FilesCommand extends ModeratedCommand
{
    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('mautic:contactclient:files')
            ->setDescription('Sends a contact to a client/queue.')
            ->addOption(
                'client',
                'c',
                InputOption::VALUE_OPTIONAL,
                'The contact client/s to send a file for. Otherwise all appropriate clients will be sent to.',
                null
            )
            ->addOption(
                'test',
                'i',
                InputOption::VALUE_NONE,
                'Run client requests in test mode.'
            )
            ->addOption(
                'mode',
                'm',
                InputOption::VALUE_OPTIONAL,
                'build, send, or both (default).'
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

        if (!$this->checkRunStatus($input, $output, $options['client'].$options['mode'])) {
            return 0;
        }

        if (!empty($options['client']) && !is_numeric($options['client'])) {
            $output->writeln('<error>'.$translator->trans('mautic.contactclient.sendcontact.error.client').'</error>');

            return 1;
        }

        $mode = isset($options['mode']) ? strtolower(trim($options['mode'])) : 'both';
        if (!in_array($mode, ['both', 'build', 'send'])) {
            $output->writeln('<error>'.$translator->trans('mautic.contactclient.files.error.mode').'</error>');

            return 1;
        }

        $filter = [];
        if (!empty($options['client'])) {
            $filter['where'][] = [
                'column' => 'f.id',
                'expr'   => 'eq',
                'value'  => (int) $options['client'],
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

        while (false !== ($client = $clients->next())) {
            $client = reset($client);
            // The client must still be published, and must still be set to send files.
            if (
                true === $client->getIsPublished()
                && 'file' == $client->getType()
            ) {
                $clientId   = $client->getId();
                $clientName = $client->getName();

                try {
                    $payloadModel->reset()
                        ->setContactClient($client)
                        ->setTest($options['test']);

                    if (in_array($mode, ['build', 'both'])) {
                        $output->writeln(
                            '<info>'.$translator->trans(
                                'mautic.contactclient.files.building',
                                ['%clientId%' => $clientId, '%clientName%' => $clientName]
                            ).'</info>'
                        );
                        $payloadModel->run('build');
                    }

                    if (in_array($mode, ['send', 'both'])) {
                        $output->writeln(
                            '<info>'.$translator->trans(
                                'mautic.contactclient.files.sending',
                                ['%clientId%' => $clientId, '%clientName%' => $clientName]
                            ).'</info>'
                        );
                        $payloadModel->run('send');
                    }

                    if (isset($options['verbose']) && $options['verbose']) {
                        $output->writeln('<info>'.$payloadModel->getLogsYAML().'</info>');
                    }
                } catch (Exception $e) {
                    $output->writeln(
                        $translator->trans(
                            'mautic.contactclient.files.error',
                            ['%clientId%' => $clientId, '%clientName%' => $clientName, '%message%' => $e->getMessage()]
                        )
                    );
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
