<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 *
 * Example Use:  mautic:contactclient:yamlfix [bundle] [start] [limit]
 *               mautic:contactclient:yamlfix client
 *               mautic:contactclient:yamlfix client 25678 100000
 *               mautic:contactclient:yamlfix source 25678
 *
 * args: bundle - either client or source
 *       start  - the event table id to start from
 *       limit  - maximum number of rows to process
 */

namespace MauticPlugin\MauticContactClientBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * CLI Command : Sends a contact to a client.
 *
 * php app/console mautic:contactclient:send [--client=%clientId% [--contact=%contactId%] [--test]]
 */
class YAMLDataFixCommand extends ModeratedCommand implements ContainerAwareInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('mautic:contactclient:yamlfix')
            ->setDescription(
                'Convert YAML formatted log data to JSON in contactsource_events or contactclient_events table.'
            )
            ->addArgument(
                'bundle',
                InputArgument::REQUIRED,
                'The bundle to target: client or source.'
            )
            ->addArgument(
                'start',
                InputArgument::OPTIONAL,
                'The table\'s ID to start with.'
            )
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'The max number of records to process.'
            );


        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options   = $input->getArguments();
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        // if (!$this->checkRunStatus($input, $output, $options['bundle'])) {
        //     return 0;
        // }

        if (!$options['bundle'] || !in_array($options['bundle'], ['client', 'source'])) {
            $output->writeln("<error>Bundle is required and must be 'client' or 'source' .</error>");

            return 0;
        }

        if ($options['limit'] && !is_numeric($options['limit'])) {
            $output->writeln('<error>Limit must be a number.</error>');

            return 0;
        }

        if ($options['start'] && !is_numeric($options['start'])) {
            $output->writeln('<error>Start must be a number.</error>');

            return 0;
        }

        $entity = $options['bundle'] == 'client' ? 'MauticContactClientBundle:Event' : 'MauticContactSourceBundle:Event';

        $start = !empty($options['start']) ? $options['start'] : $this->getFirstID($em, $entity);
        $last  = !empty($options['limit']) ? $options['limit'] : $this->getLastID($em, $entity);
        $count = $last - $start;
        $updated=0;
        $batch = 1;
        $previous = $original = $start;
        $time_start = microtime(true);

        $output->writeln("<info>Converting $count $entity logs from YAML to JSON, startung at $start and ending at $last.</info>");

        while ($start <= $count) {
            $id     = $start;
            $result = $this->getLogByID($em, $id, $entity);
            $logBlob = $result->getLogs();
            if ($logBlob[0] != '{') // is YAML
            {
                //convert YAML to array
                $array = YAML::Parse($logBlob);

                // convert array to JSON
                $json = json_encode($array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

                // save record back to DB
                $result->setLogs($json);
                $this->saveJSONLog($em, $result, $entity);
                $timeSoFar = microtime(true);
                $soFar = ($timeSoFar - $time_start)/60;
                $percent = number_format($updated/$count * 100, 2, '.', ',');
                //$output->writeln("<info> >>> $id: Saved $entity Entity in Batch # $batch. (Elapsed Time So Far: $soFar)</info>");


                //batch the SQL
                if (($start % 300) === 0) {
                    $em->flush();
                    $em->clear(); // Detaches all objects from Doctrine!
                    $output->writeln("<question>Batch # $batch flushed.</question>");
                    $output->writeln("<info> >>> Saved $entity Entity ids $previous - $start. (Elapsed Time So Far: $soFar minutes, $percent%.)</info>");
                    $batch++;
                    $previous = $start;

                }

                $updated++;

            } else {
                $output->writeln("<comment> >>> Skipping $id.</comment>");

            }
            usleep(50);
            $start++;
        }

        $this->completeRun();

        return 0;
        $output->writeln("<info>Complete With No Errors. $count records processed, $updated records updated.</info>");
        $time_end = microtime(true);
        $elapsedTime = $time_end - $time_start;
        $output->writeln("<info>Total Execution Time: $elapsedTime.</info>");
    }

    protected function getFirstID($em, $entity)
    {
        $result  = $em->getRepository($entity)->findBy(array(),array('id' => 'ASC'),1);

        return $result[0]->getId();
    }

    protected function getLastID($em, $entity)
    {
        $result  = $em->getRepository($entity)->findBy(array(),array('id' => 'DESC'),1);

        return $result[0]->getId();
    }

    protected function saveJSONLog($em, $result, $entity)
    {
        $em->persist($result);
    }

    protected function getLogByID($em, $id, $entity)
    {
        $log = $em
            ->getRepository($entity)
            ->find($id);

        return $log;
    }
}
