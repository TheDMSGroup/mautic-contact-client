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
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class Stat.
 */
class Stat
{
    // Used for querying stats
    const TYPE_FORM         = 'submission';
    const TYPE_CLICK        = 'click';
    const TYPE_NOTIFICATION = 'view';

    /**
     * @var int
     */
    private $id;

    /**
     * @var ContactClient
     */
    private $contactclient;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $typeId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var
     */
    private $lead;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contactclient_stats')
            ->setCustomRepositoryClass('MauticPlugin\MauticContactClientBundle\Entity\StatRepository')
            ->addIndex(['type'], 'contactclient_type')
            ->addIndex(['date_added'], 'contactclient_date_added');

        $builder->addId();

        $builder->createManyToOne('contactclient', 'ContactClient')
            ->addJoinColumn('contactclient_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addField('type', 'string');

        $builder->addNamedField('dateAdded', 'datetime', 'date_added');

        $builder->addLead(true, 'SET NULL');
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getContactClient()
    {
        return $this->contactclient;
    }

    /**
     * @param mixed $contactclient
     *
     * @return Stat
     */
    public function setContactClient($contactclient)
    {
        $this->contactclient = $contactclient;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return Stat
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param mixed $typeId
     *
     * @return Stat
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     *
     * @return Stat
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param Lead $lead
     *
     * @return Stat
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }
}
