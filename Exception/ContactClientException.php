<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Exception;

use Mautic\LeadBundle\Entity\Lead as Contact;

/**
 * Class ContactClientException
 *
 * This form of exception indicates that we may re-try the send at a later date or time.
 * Also can indicate a Stat type for logging.
 *
 * @package MauticPlugin\MauticContactClientBundle\Exception
 */
class ContactClientException extends \Exception
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

    /**
     * ContactClientException constructor.
     *
     * ContactClientException constructor.
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     * @param null $statType
     * @param bool $retry
     * @param array $data
     */
    public function __construct(
        $message = 'Contact Client retry error',
        $code = 0,
        \Exception $previous = null,
        $statType = null,
        $retry = true,
        $data = []
    ) {
        if ($statType) {
            $this->setStatType($statType);
        }
        $this->retry = $retry;
        $this->data = $data;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getContactId()
    {
        return $this->contactId;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
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
