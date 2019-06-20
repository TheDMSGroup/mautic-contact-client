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
use MauticPlugin\MauticContactClientBundle\Entity\CacheRepository;
use MauticPlugin\MauticContactClientBundle\Model\Cache;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->setDescription('Performs maintenance tasks required by the client plugin.')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit the number of rows to delete per batch',
                10000
            )
            ->addOption(
                'delay',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Alter the delay between deletions by second.',
                1
            );

        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $translator = $container->get('translator');
        $limit      = (int) $input->getOption('limit');
        $delay      = (int) $input->getOption('delay');
        if (!$this->checkRunStatus($input, $output)) {
            return 0;
        }

        /** @var Cache $cacheModel */
        $cacheModel = $container->get('mautic.contactclient.model.cache');
        /** @var CacheRepository $cacheRepo */
        $cacheRepo = $cacheModel->getRepository();
        $output->writeln(
            '<info>'.$translator->trans(
                'mautic.contactclient.maintenance.running'
            ).'</info>'
        );
        $deleted = $cacheRepo->deleteExpired($limit, $delay);
        $output->writeln('Deleted '.$deleted.' expired cache entries.');
        $reduced = $cacheRepo->reduceExclusivityIndex($limit, $delay);
        $output->writeln('Reduced '.$reduced.' exclusivity cache entries.');
        $output->writeln(
            '<info>'.$translator->trans(
                'mautic.contactclient.maintenance.complete'
            ).'</info>'
        );

        $this->completeRun();

        return 0;
    }
}
