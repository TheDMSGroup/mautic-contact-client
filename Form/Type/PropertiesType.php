<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PropertiesType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'bar',
            'contactclient_properties',
            [
                'contactclient_style' => 'bar',
                'data'        => (isset($options['data']['bar'])) ? $options['data']['bar'] : [],
            ]
        );

        $builder->add(
            'modal',
            'contactclient_properties',
            [
                'contactclient_style' => 'modal',
                'data'        => (isset($options['data']['modal'])) ? $options['data']['modal'] : [],
            ]
        );

        $builder->add(
            'notification',
            'contactclient_properties',
            [
                'contactclient_style' => 'notification',
                'data'        => (isset($options['data']['notification'])) ? $options['data']['notification'] : [],
            ]
        );

        $builder->add(
            'page',
            'contactclient_properties',
            [
                'contactclient_style' => 'page',
                'data'        => (isset($options['data']['page'])) ? $options['data']['page'] : [],
            ]
        );

        $builder->add(
            'animate',
            'yesno_button_group',
            [
                'label' => 'mautic.contactclient.form.animate',
                'data'  => (isset($options['data']['animate'])) ? $options['data']['animate'] : true,
                'attr'  => [
                    'onchange' => 'Mautic.contactclientUpdatePreview()',
                ],
            ]
        );

        $builder->add(
            'link_activation',
            'yesno_button_group',
            [
                'label' => 'mautic.contactclient.form.activate_for_links',
                'data'  => (isset($options['data']['link_activation'])) ? $options['data']['link_activation'] : true,
                'attr'  => [
                    'data-show-on' => '{"contactclient_properties_when": ["leave"]}',
                ],
            ]
        );

        $builder->add(
            'colors',
            'contactclient_color',
            [
                'label' => false,
            ]
        );

        $builder->add(
            'content',
            'contactclient_content',
            [
                'label' => false,
            ]
        );

        $builder->add(
            'when',
            'choice',
            [
                'choices' => [
                    'immediately'   => 'mautic.contactclient.form.when.immediately',
                    'scroll_slight' => 'mautic.contactclient.form.when.scroll_slight',
                    'scroll_middle' => 'mautic.contactclient.form.when.scroll_middle',
                    'scroll_bottom' => 'mautic.contactclient.form.when.scroll_bottom',
                    'leave'         => 'mautic.contactclient.form.when.leave',
                ],
                'label'       => 'mautic.contactclient.form.when',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'expanded'    => false,
                'multiple'    => false,
                'required'    => false,
                'empty_value' => false,
            ]
        );

        $builder->add(
            'timeout',
            'text',
            [
                'label'      => 'mautic.contactclient.form.timeout',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'          => 'form-control',
                    'postaddon_text' => 'sec',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'frequency',
            'choice',
            [
                'choices' => [
                    'everypage' => 'mautic.contactclient.form.frequency.everypage',
                    'once'      => 'mautic.contactclient.form.frequency.once',
                    'q2min'     => 'mautic.contactclient.form.frequency.q2m',
                    'q15min'    => 'mautic.contactclient.form.frequency.q15m',
                    'hourly'    => 'mautic.contactclient.form.frequency.hourly',
                    'daily'     => 'mautic.contactclient.form.frequency.daily',
                ],
                'label'       => 'mautic.contactclient.form.frequency',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'expanded'    => false,
                'multiple'    => false,
                'required'    => false,
                'empty_value' => false,
            ]
        );

        $builder->add(
            'stop_after_conversion',
            'yesno_button_group',
            [
                'label' => 'mautic.contactclient.form.engage_after_conversion',
                'data'  => (isset($options['data']['stop_after_conversion'])) ? $options['data']['stop_after_conversion'] : true,
                'attr'  => [
                    'tooltip' => 'mautic.contactclient.form.engage_after_conversion.tooltip',
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'contactclient_entity_properties';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'label' => false,
            ]
        );
    }
}
