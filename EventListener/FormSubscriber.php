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
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;

/**
 * Class FormSubscriber.
 */
class FormSubscriber extends CommonSubscriber
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
            FormEvents::FORM_POST_SAVE   => ['onFormPostSave', 0],
            FormEvents::FORM_POST_DELETE => ['onFormDelete', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     *
     * @param Events\FormEvent $event
     */
    public function onFormPostSave(Events\FormEvent $event)
    {
        $form = $event->getForm();

        if ($event->isNew()) {
            return;
        }

        $foci = $this->model->getRepository()->findByForm($form->getId());

        if (empty($foci)) {
            return;
        }

        // Rebuild each contactclient
        /** @var \MauticPlugin\MauticContactClientBundle\Entity\ContactClient $contactclient */
        foreach ($foci as $contactclient) {
            $contactclient->setCache(
                $this->model->generateJavascript($contactclient)
            );
        }

        $this->model->saveEntities($foci);
    }

    /**
     * Add a delete entry to the audit log.
     *
     * @param Events\FormEvent $event
     */
    public function onFormDelete(Events\FormEvent $event)
    {
        $form   = $event->getForm();
        $formId = $form->deletedId;
        $foci   = $this->model->getRepository()->findByForm($formId);

        if (empty($foci)) {
            return;
        }

        // Rebuild each contactclient
        /** @var \MauticPlugin\MauticContactClientBundle\Entity\ContactClient $contactclient */
        foreach ($foci as $contactclient) {
            $contactclient->setForm(null);
            $contactclient->setCache(
                $this->model->generateJavascript($contactclient)
            );
        }

        $this->model->saveEntities($foci);
    }
}
