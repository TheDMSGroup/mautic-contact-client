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

use Mautic\CoreBundle\Command\ModeratedCommand;
use MauticPlugin\MauticContactClientBundle\Model\Cache;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command : Performs maintenance tasks required by the client plugin.
 *
 * php app/console mautic:contactclient:maintenance
 */
class MaintenanceCommand extends ModeratedCommand
{
    /**
     * Maintenance command line task.
     */
    protected function configure()
    {
        $this->setName('mautic:contactclient:maintenance')
            ->setDescription('Performs maintenance tasks required by the client plugin.');

        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $translator = $container->get('translator');
        if (!$this->checkRunStatus($input, $output)) {
            return 0;
        }

        /** @var Cache $cacheModel */
        $cacheModel = $container->get('mautic.contactclient.model.cache');
        $output->writeln(
            '<info>'.$translator->trans(
                'mautic.contactclient.maintenance.running'
            ).'</info>'
        );
        $cacheModel->getRepository()
            ->deleteExpired()
            ->reduceExclusivityIndex();
        $output->writeln(
            '<info>'.$translator->trans(
                'mautic.contactclient.maintenance.complete'
            ).'</info>'
        );

        $this->completeRun();

        return 0;
    }
}
