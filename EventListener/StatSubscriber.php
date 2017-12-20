<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;

/**
 * Class StatSubscriber.
 */
class StatSubscriber extends CommonSubscriber
{
    /**
     * @var ContactClientModel
     */
    protected $model;

    /**
     * FormSubscriber constructor.
     *
     * @param ContactClientModel $model
     */
    public function __construct(ContactClientModel $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_HIT    => ['onPageHit', 0],
            FormEvents::FORM_ON_SUBMIT => ['onFormSubmit', 0],
        ];
    }

    /**
     * @param PageHitEvent $event
     */
    public function onPageHit(PageHitEvent $event)
    {
        $hit    = $event->getHit();
        $source = $hit->getSource();

        if ($source == 'contactclient' || $source == 'contactclient.contactclient') {
            $sourceId = $hit->getSourceId();
            $contactclient    = $this->model->getEntity($sourceId);

            if ($contactclient && $contactclient->isPublished()) {
                $this->model->addStat($contactclient, Stat::TYPE_CLICK, $hit, $hit->getLead());
            }
        }
    }

    /**
     * Note if this submission is from a contactclient submit.
     *
     * @param SubmissionEvent $event
     */
    public function onFormSubmit(SubmissionEvent $event)
    {
        // Check the request for a contactclient field
        $id = $this->request->request->get('mauticform[contactclientId]', false, true);

        if (!empty($id)) {
            $contactclient = $this->model->getEntity($id);

            if ($contactclient && $contactclient->isPublished()) {
                // Make sure the form is still applicable
                $form = $event->getSubmission()->getForm();
                if ((int) $form->getId() === (int) $contactclient->getForm()) {
                    $this->model->addStat($contactclient, Stat::TYPE_FORM, $event->getSubmission(), $event->getLead());
                }
            }
        }
    }
}
