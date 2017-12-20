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

class ColorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'primary',
            'text',
            [
                'label'      => 'mautic.contactclient.form.primary_color',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'color',
                    'onchange'    => 'Mautic.contactclientUpdatePreview()',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'text',
            'text',
            [
                'label'      => 'mautic.contactclient.form.text_color',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'color',
                    'onchange'    => 'Mautic.contactclientUpdatePreview()',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'button',
            'text',
            [
                'label'      => 'mautic.contactclient.form.button_color',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'color',
                    'onchange'    => 'Mautic.contactclientUpdatePreview()',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'button_text',
            'text',
            [
                'label'      => 'mautic.contactclient.form.button_text_color',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'color',
                    'onchange'    => 'Mautic.contactclientUpdatePreview()',
                ],
                'required' => false,
            ]
        );
    }

    public function getName()
    {
        return 'contactclient_color';
    }
}
