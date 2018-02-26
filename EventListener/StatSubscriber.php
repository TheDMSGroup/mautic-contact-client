<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
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
        return [];
    }
}
