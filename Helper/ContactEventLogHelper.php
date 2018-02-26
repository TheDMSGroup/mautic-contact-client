<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Helper;


use Mautic\LeadBundle\Entity\Lead as Contact;
use Mautic\LeadBundle\Entity\LeadEventLog as ContactEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository as ContactEventLogRepository;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;

/**
 * Class ContactEventLogHelper
 *
 * NOTE: This is not currently used for contact clients. See notes in the integrator.
 *
 * @package MauticPlugin\MauticContactClientBundle\Helper
 */
class ContactEventLogHelper
{

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var ContactEventLogRepository
     */
    protected $ContactEventLogRepo;

    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel           = $leadModel;
        $this->ContactEventLogRepo = $leadModel->getEventLogRepository();
    }

    /**
     * Save log about errored line.
     *
     * @param ContactEventLog $eventLog
     * @param string          $errorMessage
     */
    public function logError(ContactEventLog $eventLog, $errorMessage)
    {
        $eventLog->addProperty('error', $this->translator->trans($errorMessage))
            ->setAction('failed');
        $this->ContactEventLogRepo->saveEntity($eventLog);
        $this->logDebug('Line '. 1 .' error: '.'err', []);
    }

    /**
     * Initialize ContactEventLog object and configure it as the import event.
     *
     * @param ContactClient $client
     * @param Contact       $contact
     * @param               $lineNumber
     *
     * @return ContactEventLog
     */
    public function initEventLog(ContactClient $client, Contact $contact, $lineNumber)
    {
        $eventLog = new ContactEventLog();
        $eventLog->setUserId($client->getCreatedBy())
            ->setUserName($client->getCreatedByUser())
            ->setBundle('lead')
//            ->setObject('import')
            ->setObjectId($contact->getId())
            ->setProperties(
                [
                    'line' => $lineNumber,
                    'file' => $client->getOriginalFile(),
                ]
            );

        return $eventLog;
    }


}