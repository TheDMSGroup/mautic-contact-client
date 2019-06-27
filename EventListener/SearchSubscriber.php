<?php

namespace MauticPlugin\MauticContactClientBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;

class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var ContactClientModel
     */
    protected $clientModel;

    /**
     * SearchSubscriber constructor.
     *
     * @param ContactClientModel $clientModel
     */
    public function __construct(ContactClientModel $clientModel)
    {
        $this->clientModel = $clientModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::GLOBAL_SEARCH => ['onGlobalSearch', 0],
        ];
    }

    /**
     * @param GlobalSearchEvent $event
     */
    public function onGlobalSearch(GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        $filter = ['string' => $str, 'force' => ''];

        $permissions = $this->security->isGranted(
            ['plugin:contactclient:items:view'],
            'RETURN_ARRAY'
        );

        if ($permissions) {
            $results = $this->clientModel->getEntities(
                [
                    'limit'          => 5,
                    'filter'         => $filter,
                    'withTotalCount' => true,
                ]
            );

            $count = $results->count();

            if ($count > 0) {
                $clients       = $results->getQuery()->execute();
                $clientResults = [];

                foreach ($clients as $client) {
                    $clientResults[] = $this->templating->renderResponse(
                        'MauticContactClientBundle:SubscribedEvents\Search:global.html.php',
                        ['client' => $client]
                    )->getContent();
                }

                if ($count > 5) {
                    $clientResults[] = $this->templating->renderResponse(
                        'MauticContactClientBundle:SubscribedEvents\Search:global.html.php',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($count - 5),
                        ]
                    )->getContent();
                }
                $clientResults['count'] = $count;
                $event->addResults('mautic.contactclient', $clientResults);
            }
        }
    }
}
