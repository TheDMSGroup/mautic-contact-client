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


use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Entity\Lead as Contact;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;

/**
 * Class ContactLogHelper.
 */
class ContactEventLogHelper
{

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var LeadEventLogRepository
     */
    protected $leadEventLogRepo;

    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
        $this->leadEventLogRepo = $leadModel->getEventLogRepository();
    }

    /**
     * Save log about errored line.
     *
     * @param LeadEventLog $eventLog
     * @param string $errorMessage
     */
    public function logError(LeadEventLog $eventLog, $errorMessage)
    {
        $eventLog->addProperty('error', $this->translator->trans($errorMessage))
            ->setAction('failed');
        $this->leadEventLogRepo->saveEntity($eventLog);
        $this->logDebug('Line '. 1 .' error: '.'err', []);
    }

    /**
     * Initialize LeadEventLog object and configure it as the import event.
     *
     * @param ContactClient $client
     * @param Contact $contact
     * @param $lineNumber
     * @return LeadEventLog
     */
    public function initEventLog(ContactClient $client, Contact $contact, $lineNumber)
    {
        $eventLog = new LeadEventLog();
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