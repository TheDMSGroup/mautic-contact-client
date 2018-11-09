<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Auth
 *
 * Use to store re-usable authentication tokens for the Clients that need them (oAuth1a for example).
 */
class Auth
{
    /** @var int $id */
    private $id;

    /** @var int $contactClient */
    private $contactClient;

    /** @var string $key */
    private $key;

    /** @var string $value */
    private $value;

    /** @var \DateTime $dateAdded */
    private $dateAdded;

    /**
     * Cache constructor.
     */
    public function __construct()
    {
        $this->dateAdded = new \DateTime();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();

        $builder->setTable('contactclient_auth');

        $builder->addNullableField('key', 'string');

        $builder->addNullableField('value', 'string');

        $builder->addNamedField('contactClient', 'integer', 'contactclient_id');

        $builder->addDateAdded();

        $builder->addIndex(
            [
                'contactclient_id',
            ],
            'contactclient_auth_contactclient_id'
        );

        $builder->setCustomRepositoryClass('MauticPlugin\MauticContactClientBundle\Entity\AuthRepository');
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getContactClient()
    {
        return $this->contactClient;
    }

    /**
     * @param int $contactClient
     *
     * @return $this
     */
    public function setContactClient($contactClient)
    {
        $this->contactClient = $contactClient;

        return $this;
    }

}
