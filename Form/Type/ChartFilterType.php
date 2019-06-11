<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Form\Type;

use DateTime;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Factory\MauticFactory;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FilterType.
 */
class ChartFilterType extends AbstractType
{
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $request         = Request::createFromGlobals();
        $contactClientId = $request->get('contactclient');
        if (!is_integer($contactClientId)) {
            if (isset($options['action'])) {
                $parts           = explode('/', $options['action']);
                $contactClientId = intval(end($parts));
            }
        }

        $campaigns = [];
        /* @var Campaign $campaign */
        if ($contactClientId) {
            foreach ($this->factory->getModel('contactclient')->getCampaigns($contactClientId) as $campaign) {
                $campaigns[$campaign->getId()] = $campaign->getName();
            }
        }

        $builder->add(
            'campaign',
            ChoiceType::class,
            [
                'choices'    => $campaigns,
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.contactclient.integration.campaign.tooltip',
                ],
                'expanded'   => false,
                'multiple'   => false,
                'label'      => 'mautic.contactclient.transactions.campaign_select',
                'label_attr' => ['class' => 'control-label'],
                'empty_data' => 'All Campaigns',
                'required'   => false,
                'disabled'   => false,
                'data'       => $options['data']['campaign'],
            ]
        );

        $typeChoices = [
            // 'All Events' => 'All Events',
            // ''           => '--- By Source ---',
            'revenue' => 'Revenue',
        ];
        $stat        = new Stat();
        foreach ($stat->getAllTypes() as $type) {
            $typeChoices[$type] = 'mautic.contactclient.graph.'.$type;
        }

        $builder->add(
            'type',
            'choice',
            [
                'choices'     => $typeChoices,
                'attr'        => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.contactclient.transactions.event_type_tooltip',
                ],
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.contactclient.transactions.event_type',
                'empty_data'  => 'All Events',
                'required'    => false,
                'disabled'    => false,
                'placeholder' => 'All Sources By Events',
                'group_by'    => function ($value, $key, $index) {
                    return 'By Source';
                },
            ]
        );

        $humanFormat = 'M j, Y';

        $dateFrom = (empty($options['data']['date_from']))
            ? new DateTime('-1 month')
            : new DateTime($options['data']['date_from']);
        $builder->add(
            'date_from',
            'text',
            [
                'label'      => 'mautic.core.date.from',
                'label_attr' => ['class' => 'control-label', 'autocomplete' => 'off'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
                'data'       => $dateFrom->format($humanFormat),
            ]
        );

        $dateTo = (empty($options['data']['date_to']))
            ? new DateTime()
            : new DateTime($options['data']['date_to']);

        $builder->add(
            'date_to',
            'text',
            [
                'label'      => 'mautic.core.date.to',
                'label_attr' => ['class' => 'control-label', 'autocomplete' => 'off'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
                'data'       => $dateTo->format($humanFormat),
            ]
        );

        $builder->add(
            'apply',
            'submit',
            [
                'label' => 'mautic.core.form.apply',
                'attr'  => ['class' => 'btn btn-default'],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'contactclient_chart';
    }
}
