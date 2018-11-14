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
 * Class Auth.
 *
 * Use to store re-usable authentication tokens for the Clients that need them (oAuth1a for example).
 */
class Auth
{
    /** @var int $id */
    private $id;

    /** @var int $contactClient */
    private $contactClient;

    /** @var string $field */
    private $field;

    /** @var string $value */
    private $val;

    /** @var \DateTime $dateAdded */
    private $dateAdded;

    /** @var bool */
    private $test;

    /** @var int */
    private $operation;

    /** @var string */
    private $type;

    /**
     * Cache constructor.
     */
    public function __construct()
    {
        $this->dateAdded = new \DateTime();
        $this->test      = false;
        $this->operation = 0;
        $this->type      = 'global';
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contactclient_auth');

        $builder->addId();

        $builder->addNamedField('contactClient', 'integer', 'contactclient_id');

        $builder->addNamedField('operation', 'integer', 'operation');

        $builder->addNamedField('type', 'string', 'type');

        $builder->addNullableField('field', 'string');

        $builder->addNullableField('val', 'text');

        $builder->createField('test', 'boolean')
            ->columnName('test')
            ->build();

        $builder->addDateAdded();

        $builder->addIndex(
            [
                'contactclient_id',
                'test',
            ],
            'contactclient_auth_contactclient_id_test'
        );

        $builder->setCustomRepositoryClass('MauticPlugin\MauticContactClientBundle\Entity\AuthRepository');
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param $operation
     *
     * @return $this
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * @return bool
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @param $test
     *
     * @return $this
     */
    public function setTest($test)
    {
        $this->test = $test;

        return $this;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     *
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return string
     */
    public function getVal()
    {
        return $this->val;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setVal($val)
    {
        $this->val = $val;

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
