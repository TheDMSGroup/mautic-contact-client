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

class ContactClientPropertiesType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Type specific
        switch ($options['contactclient_style']) {
            case 'bar':
                $builder->add(
                    'allow_hide',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.contactclient.form.bar.allow_hide',
                        'data'  => (isset($options['data']['allow_hide'])) ? $options['data']['allow_hide'] : true,
                        'attr'  => [
                            'onchange' => 'Mautic.contactclientUpdatePreview()',
                        ],
                    ]
                );

                $builder->add(
                    'push_page',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.contactclient.form.bar.push_page',
                        'attr'  => [
                            'tooltip'  => 'mautic.contactclient.form.bar.push_page.tooltip',
                            'onchange' => 'Mautic.contactclientUpdatePreview()',
                        ],
                        'data' => (isset($options['data']['push_page'])) ? $options['data']['push_page'] : true,
                    ]
                );

                $builder->add(
                    'sticky',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.contactclient.form.bar.sticky',
                        'attr'  => [
                            'tooltip'  => 'mautic.contactclient.form.bar.sticky.tooltip',
                            'onchange' => 'Mautic.contactclientUpdatePreview()',
                        ],
                        'data' => (isset($options['data']['sticky'])) ? $options['data']['sticky'] : true,
                    ]
                );

                $builder->add(
                    'size',
                    'choice',
                    [
                        'choices' => [
                            'large'   => 'mautic.contactclient.form.bar.size.large',
                            'regular' => 'mautic.contactclient.form.bar.size.regular',
                        ],
                        'label'      => 'mautic.contactclient.form.bar.size',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'    => 'form-control',
                            'onchange' => 'Mautic.contactclientUpdatePreview()',
                        ],
                        'required'    => false,
                        'empty_value' => false,
                    ]
                );

                $choices = [
                    'top'    => 'mautic.contactclient.form.placement.top',
                    'bottom' => 'mautic.contactclient.form.placement.bottom',
                ];
                break;
            case 'modal':
                $choices = [
                    'top'    => 'mautic.contactclient.form.placement.top',
                    'middle' => 'mautic.contactclient.form.placement.middle',
                    'bottom' => 'mautic.contactclient.form.placement.bottom',
                ];
                break;
            case 'notification':
                $choices = [
                    'top_left'     => 'mautic.contactclient.form.placement.top_left',
                    'top_right'    => 'mautic.contactclient.form.placement.top_right',
                    'bottom_left'  => 'mautic.contactclient.form.placement.bottom_left',
                    'bottom_right' => 'mautic.contactclient.form.placement.bottom_right',
                ];
                break;
            case 'page':
                break;
        }

        if (!empty($choices)) {
            $builder->add(
                'placement',
                'choice',
                [
                    'choices'    => $choices,
                    'label'      => 'mautic.contactclient.form.placement',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control',
                        'onchange' => 'Mautic.contactclientUpdatePreview()',
                    ],
                    'required'    => false,
                    'empty_value' => false,
                ]
            );
        }
    }

    public function getName()
    {
        return 'contactclient_properties';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['contactclient_style']);

        $resolver->setDefaults(
            [
                'label' => false,
            ]
        );
    }
}
