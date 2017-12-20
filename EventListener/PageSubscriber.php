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
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class PageSubscriber.
 */
class PageSubscriber extends CommonSubscriber
{
    private $regex = '{contactclient=(.*?)}';

    /**
     * @var ContactClientModel
     */
    protected $model;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * PageSubscriber constructor.
     *
     * @param ContactClientModel      $model
     * @param RouterInterface $router
     */
    public function __construct(ContactClientModel $model, RouterInterface $router)
    {
        $this->router = $router;
        $this->model  = $model;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_DISPLAY => ['onPageDisplay', 0],
            PageEvents::PAGE_ON_BUILD   => ['onPageBuild', 0],
        ];
    }

    /**
     * Add forms to available page tokens.
     *
     * @param PageBuilderEvent $event
     */
    public function onPageBuild(PageBuilderEvent $event)
    {
        if ($event->tokensRequested($this->regex)) {
            $tokenHelper = new BuilderTokenHelper($this->factory, 'contactclient', $this->model->getPermissionBase(), 'MauticContactClientBundle', 'mautic.contactclient');
            $event->addTokensFromHelper($tokenHelper, $this->regex, 'name', 'id', true);
        }
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function onPageDisplay(PageDisplayEvent $event)
    {
        $content = $event->getContent();
        $regex   = '/'.$this->regex.'/i';

        preg_match_all($regex, $content, $matches);

        if (count($matches[0])) {
            foreach ($matches[1] as $k => $id) {
                $contactclient = $this->model->getEntity($id);
                if ($contactclient !== null
                    && (
                        $contactclient->isPublished()
                        || $this->security->hasEntityAccess(
                            'plugin:contactclient:items:viewown',
                            'plugin:contactclient:items:viewother',
                            $contactclient->getCreatedBy()
                        )
                    )
                ) {
                    $script = '<script src="'.$this->router->generate('mautic_contactclient_generate', ['id' => $id], true)
                        .'" type="text/javascript" charset="utf-8" async="async"></script>';
                    $content = preg_replace('#{contactclient='.$id.'}#', $script, $content);
                } else {
                    $content = preg_replace('#{contactclient='.$id.'}#', '', $content);
                }
            }
        }
        $event->setContent($content);
    }
}
