<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class FailedContactEventLog.
 */
class FailedContactEventLog
{
    /**
     * @var ContactEventLog
     */
    private $log;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var string
     */
    private $reason;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contactclient_contact_event_failed_log')
            ->setCustomRepositoryClass(FailedContactEventLogRepository::class)
            ->addIndex(['date_added'], 'contactclient_event_failed_date');

        $builder->createOneToOne('log', 'ContactEventLog')
            ->makePrimaryKey()
            ->inversedBy('failedLog')
            ->addJoinColumn('log_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addDateAdded();

        $builder->addNullableField('reason', 'text');
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('contactClientEventFailedLog')
            ->addProperties(
                [
                    'dateAdded',
                    'reason',
                ]
            )
            ->build();
    }

    /**
     * @return ContactEventLog
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param ContactEventLog $log
     *
     * @return FailedContactEventLog
     */
    public function setLog(ContactEventLog $log = null)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $dateAdded
     *
     * @return FailedContactEventLog
     */
    public function setDateAdded(\DateTime $dateAdded = null)
    {
        if (null === $dateAdded) {
            $dateAdded = new \DateTime();
        }

        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     *
     * @return FailedContactEventLog
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }
}
