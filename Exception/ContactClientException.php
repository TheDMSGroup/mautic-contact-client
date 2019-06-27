<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Exception;

use Exception;
use Mautic\LeadBundle\Entity\Lead as Contact;

/**
 * Class ContactClientException.
 *
 * This form of exception indicates that we may re-try the send at a later date or time.
 * Also can indicate a Stat type for logging.
 */
class ContactClientException extends Exception
{
    /** @var string */
    private $contactId;

    /** @var Contact */
    private $contact;

    /** @var string */
    private $statType;

    /** @var bool */
    private $retry;

    /** @var array */
    private $data;

    /** @var string */
    private $field;

    /**
     * ContactClientException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     * @param string         $statType
     * @param bool           $retry    indicates this send *can* be retried if the client is configured to allow it
     * @param null           $field
     * @param array          $data
     */
    public function __construct(
        $message = 'Contact Client retry error',
        $code = 0,
        Exception $previous = null,
        $statType = '',
        $retry = true,
        $field = null,
        $data = []
    ) {
        $this->statType = $statType;
        $this->retry    = $retry;
        $this->field    = $field;
        $this->data     = $data;
        parent::__construct($message, $code, $previous);
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
     * @return mixed
     */
    public function getContactId()
    {
        return $this->contactId;
    }

    /**
     * @param mixed $contactId
     *
     * @return ContactClientException
     */
    public function setContactId($contactId)
    {
        $this->contactId = $contactId;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getStatType()
    {
        return $this->statType;
    }

    /**
     * @param string $statType
     *
     * @return ContactClientException
     */
    public function setStatType($statType)
    {
        $this->statType = $statType;

        return $this;
    }

    /**
     * @param string $message
     *
     * @return ContactClientException
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param Contact $contact
     *
     * @return ContactClientException
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return bool
     */
    public function getRetry()
    {
        return $this->retry;
    }

    /**
     * @param bool $retry
     *
     * @return ContactClientException
     */
    public function setRetry($retry)
    {
        $this->retry = $retry;

        return $this;
    }
}
