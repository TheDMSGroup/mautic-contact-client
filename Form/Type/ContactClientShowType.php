<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ContactClientShowType
 * @package MauticPlugin\MauticContactClientBundle\Form\Type
 */
class ContactClientShowType extends AbstractType
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'contactclient',
            'contactclient_list',
            [
                'label'      => 'mautic.contactclient.contactclientitem.selectitem',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.contactclient.contactclientitem.selectitem_descr',
                    'onchange' => 'Mautic.disabledContactClientActions()',
                ],
                'multiple'    => false,
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.contactclient.choosecontactclient.notblank']
                    ),
                ],
            ]
        );

        if (!empty($options['update_select'])) {
            $windowUrl = $this->router->generate(
                'mautic_contactclient_action',
                [
                    'objectAction' => 'new',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'newContactClientButton',
                'button',
                [
                    'attr' => [
                        'class'   => 'btn btn-primary btn-nospin',
                        'onclick' => 'Mautic.loadNewWindow({
                        "windowUrl": "'.$windowUrl.'"
                    })',
                        'icon' => 'fa fa-plus',
                    ],
                    'label' => 'mautic.contactclient.show.new.item',
                ]
            );

            // create button edit contactclient
            $windowUrlEdit = $this->router->generate(
                'mautic_contactclient_action',
                [
                    'objectAction' => 'edit',
                    'objectId'     => 'contactclientId',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'editContactClientButton',
                'button',
                [
                    'attr' => [
                        'class'    => 'btn btn-primary btn-nospin',
                        'onclick'  => 'Mautic.loadNewWindow(Mautic.standardContactClientUrl({"windowUrl": "'.$windowUrlEdit.'"}))',
                        'disabled' => !isset($options['data']['contactclient']),
                        'icon'     => 'fa fa-edit',
                    ],
                    'label' => 'mautic.contactclient.show.edit.item',
                ]
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['update_select']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'contactclientshow_list';
    }
}
