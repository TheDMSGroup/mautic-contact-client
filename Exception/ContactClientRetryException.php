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
 * Class ContactClientRetryException
 *
 * This form of exception indicates that we can re-try the send at a later date or time.
 *
 * @package MauticPlugin\MauticContactClientBundle\Exception
 */
class ContactClientRetryException extends \Exception
{
    /**
     * @var string
     */
    private $contactId;

    /**
     * @var Contact
     */
    private $contact;

    /**
     * @var string
     */
    private $statType;

    /**
     * ContactClientRetryException constructor.
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     * @param string $statType
     */
    public function __construct(
        $message = 'Contact Client retry error',
        $code = 0,
        \Exception $previous = null,
        $statType = null
    ) {
        if ($statType) {
            $this->setStatType($statType);
        }
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
     * @param mixed $contactId
     *
     * @return ContactClientRetryException
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
     * @return ContactClientRetryException
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
     * @return ContactClientRetryException
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }
}
