<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Event\ContactClientEvent;
use MauticPlugin\MauticContactClientBundle\ContactClientEvents;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class ContactClientModel extends FormModel
{
    /**
     * @var ContainerAwareEventDispatcher
     */
    protected $dispatcher;

    /**
     * @var \Mautic\FormBundle\Model\FormModel
     */
    protected $formModel;

    /**
     * @var TrackableModel
     */
    protected $trackableModel;

    /**
     * @var TemplatingHelper
     */
    protected $templating;

    /**
     * @var
     */
    protected $leadModel;

    /**
     * ContactClientModel constructor.
     *
     * @param \Mautic\FormBundle\Model\FormModel $formModel
     * @param TrackableModel                     $trackableModel
     * @param TemplatingHelper                   $templating
     * @param EventDispatcherInterface           $dispatcher
     * @param LeadModel                          $leadModel
     */
    public function __construct(\Mautic\FormBundle\Model\FormModel $formModel, TrackableModel $trackableModel, TemplatingHelper $templating, EventDispatcherInterface $dispatcher, LeadModel $leadModel)
    {
        $this->formModel      = $formModel;
        $this->trackableModel = $trackableModel;
        $this->templating     = $templating;
        $this->dispatcher     = $dispatcher;
        $this->leadModel      = $leadModel;
    }

    /**
     * @return string
     */
    public function getActionRouteBase()
    {
        return 'contactclient';
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return 'plugin:contactclient:items';
    }

    /**
     * {@inheritdoc}
     *
     * @param object                              $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param null                                $action
     * @param array                               $options
     *
     * @throws NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof ContactClient) {
            throw new MethodNotAllowedHttpException(['ContactClient']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('contactclient', $entity, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MauticContactClientBundle\Entity\ContactClientRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticContactClientBundle:ContactClient');
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MauticContactClientBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->em->getRepository('MauticContactClientBundle:Stat');
    }

    /**
     * {@inheritdoc}
     *
     * @param null $id
     *
     * @return ContactClient
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new ContactClient();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param ContactClient      $entity
     * @param bool|false $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        parent::saveEntity($entity, $unlock);

        // Generate cache after save to have ID available
        $content = $this->generateJavascript($entity);
        $entity->setCache($content);

        $this->getRepository()->saveEntity($entity);
    }

    /**
     * @param ContactClient $contactclient
     * @param bool  $preview
     *
     * @return string
     */
    public function generateJavascript(ContactClient $contactclient, $isPreview = false, $byPassCache = false)
    {
        // If cached is not an array, rebuild to support the new format
        $cached = json_decode($contactclient->getCache(), true);
        if ($isPreview || $byPassCache || empty($cached) || !isset($cached['js'])) {
            $contactclientArray = $contactclient->toArray();

            $url = '';
            if ($contactclientArray['type'] == 'link' && !empty($contactclientArray['properties']['content']['link_url'])) {
                $trackable = $this->trackableModel->getTrackableByUrl(
                    $contactclientArray['properties']['content']['link_url'],
                    'contactclient',
                    $contactclientArray['id']
                );

                $url = $this->trackableModel->generateTrackableUrl(
                    $trackable,
                    ['channel' => ['contactclient', $contactclientArray['id']]],
                    false,
                    $contactclient->getUtmTags()
                );
            }

            $javascript = $this->templating->getTemplating()->render(
                'MauticContactClientBundle:Builder:generate.js.php',
                [
                    'contactclient'    => $contactclientArray,
                    'preview'  => $isPreview,
                    'clickUrl' => $url,
                ]
            );

            $content = $this->getContent($contactclientArray, $isPreview, $url);
            $cached  = [
                'js'    => \Minify_HTML::minify($javascript),
                'contactclient' => \Minify_HTML::minify($content['contactclient']),
                'form'  => \Minify_HTML::minify($content['form']),
            ];

            if (!$byPassCache) {
                $contactclient->setCache(json_encode($cached));
                $this->saveEntity($contactclient);
            }
        }

        // Replace tokens to ensure clickthroughs, lead tokens etc are appropriate
        $lead       = $this->leadModel->getCurrentLead();
        $tokenEvent = new TokenReplacementEvent($cached['contactclient'], $lead, ['contactclient_id' => $contactclient->getId()]);
        $this->dispatcher->dispatch(ContactClientEvents::TOKEN_REPLACEMENT, $tokenEvent);
        $contactclientContent = $tokenEvent->getContent();
        $contactclientContent = str_replace('{contactclient_form}', $cached['form'], $contactclientContent, $formReplaced);
        if (!$formReplaced && !empty($cached['form'])) {
            // Form token missing so just append the form
            $contactclientContent .= $cached['form'];
        }

        $contactclientContent = $this->templating->getTemplating()->getEngine('MauticContactClientBundle:Builder:content.html.php')->escape($contactclientContent, 'js');

        return str_replace('{contactclient_content}', $contactclientContent, $cached['js']);
    }

    /**
     * @param array  $contactclient
     * @param bool   $isPreview
     * @param string $url
     *
     * @return array
     */
    public function getContent(array $contactclient, $isPreview = false, $url = '#')
    {
        $form = (!empty($contactclient['form'])) ? $this->formModel->getEntity($contactclient['form']) : null;

        if (isset($contactclient['html_mode'])) {
            $htmlMode = $contactclient['html_mode'];
        } elseif (isset($contactclient['htmlMode'])) {
            $htmlMode = $contactclient['htmlMode'];
        } else {
            $htmlMode = 'basic';
        }

        $content = $this->templating->getTemplating()->render(
            'MauticContactClientBundle:Builder:content.html.php',
            [
                'contactclient'    => $contactclient,
                'preview'  => $isPreview,
                'htmlMode' => $htmlMode,
                'clickUrl' => $url,
            ]
        );

        // Form has to be generated outside of the content or else the form src will be converted to clickables
        $formContent = (!empty($form)) ? $this->templating->getTemplating()->render(
            'MauticContactClientBundle:Builder:form.html.php',
            [
                'form'    => $form,
                'style'   => $contactclient['style'],
                'contactclientId' => $contactclient['id'],
                'preview' => $isPreview,
            ]
        ) : '';

        if ($isPreview) {
            $content = str_replace('{contactclient_form}', $formContent, $content, $formReplaced);
            if (!$formReplaced && !empty($formContent)) {
                $content .= $formContent;
            }

            return $content;
        }

        return [
            'contactclient' => $content,
            'form'  => $formContent,
        ];
    }

    /**
     * Get whether the color is light or dark.
     *
     * @param $hex
     * @param $level
     *
     * @return bool
     */
    public static function isLightColor($hex, $level = 200)
    {
        $hex = str_replace('#', '', $hex);
        $r   = hexdec(substr($hex, 0, 2));
        $g   = hexdec(substr($hex, 2, 2));
        $b   = hexdec(substr($hex, 4, 2));

        $compareWith = ((($r * 299) + ($g * 587) + ($b * 114)) / 1000);

        return $compareWith >= $level;
    }

    /**
     * Add a stat entry.
     *
     * @param ContactClient $contactclient
     * @param       $type
     * @param null  $data
     * @param null  $lead
     */
    public function addStat(ContactClient $contactclient, $type, $data = null, $lead = null)
    {
        switch ($type) {
            case Stat::TYPE_FORM:
                /** @var \Mautic\FormBundle\Entity\Submission $data */
                $typeId = $data->getId();
                break;
            case Stat::TYPE_NOTIFICATION:
                /** @var Request $data */
                $typeId = null;
                break;
            case Stat::TYPE_CLICK:
                /** @var \Mautic\PageBundle\Entity\Hit $data */
                $typeId = $data->getId();
                break;
        }

        $stat = new Stat();
        $stat->setContactClient($contactclient)
            ->setDateAdded(new \DateTime())
            ->setType($type)
            ->setTypeId($typeId)
            ->setLead($lead);

        $this->getStatRepository()->saveEntity($stat);
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|ContactClientEvent|void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof ContactClient) {
            throw new MethodNotAllowedHttpException(['ContactClient']);
        }

        switch ($action) {
            case 'pre_save':
                $name = ContactClientEvents::PRE_SAVE;
                break;
            case 'post_save':
                $name = ContactClientEvents::POST_SAVE;
                break;
            case 'pre_delete':
                $name = ContactClientEvents::PRE_DELETE;
                break;
            case 'post_delete':
                $name = ContactClientEvents::POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new ContactClientEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param ContactClient          $contactclient
     * @param                $unit
     * @param \DateTime|null $dateFrom
     * @param \DateTime|null $dateTo
     * @param null           $dateFormat
     * @param bool           $canViewOthers
     *
     * @return array
     */
    public function getStats(ContactClient $contactclient, $unit, \DateTime $dateFrom = null, \DateTime $dateTo = null, $dateFormat = null, $canViewOthers = true)
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo, $unit);

        $q = $query->prepareTimeDataQuery('contactclient_stats', 'date_added', ['contactclient_id' => $contactclient->getId()]);
        if (!$canViewOthers) {
            $this->limitQueryToCreator($q);
        }
        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->translator->trans('mautic.contactclient.graph.views'), $data);

        if ($contactclient->getType() != 'notification') {
            if ($contactclient->getType() == 'link') {
                $q = $query->prepareTimeDataQuery('contactclient_stats', 'date_added', ['type' => Stat::TYPE_CLICK]);
                if (!$canViewOthers) {
                    $this->limitQueryToCreator($q);
                }
                $data = $query->loadAndBuildTimeData($q);
                $chart->setDataset($this->translator->trans('mautic.contactclient.graph.clicks'), $data);
            } else {
                $q = $query->prepareTimeDataQuery('contactclient_stats', 'date_added', ['type' => Stat::TYPE_FORM]);
                if (!$canViewOthers) {
                    $this->limitQueryToCreator($q);
                }
                $data = $query->loadAndBuildTimeData($q);
                $chart->setDataset($this->translator->trans('mautic.contactclient.graph.submissions'), $data);
            }
        }

        return $chart->render();
    }

    /**
     * Joins the email table and limits created_by to currently logged in user.
     *
     * @param QueryBuilder $q
     */
    public function limitQueryToCreator(QueryBuilder $q)
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'contactclient', 'm', 'e.id = t.contactclient_id')
            ->andWhere('m.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }
}
