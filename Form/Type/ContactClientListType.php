<?php

namespace MauticPlugin\MauticContactClientBundle\Form\Type;

use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ContactClientListType.
 */
class ContactClientListType extends AbstractType
{
    /**
     * @var ContactClientModel
     */
    protected $contactclientModel;

    private $repo;

    /**
     * @param ContactClientModel $contactclientModel
     */
    public function __construct(ContactClientModel $contactclientModel)
    {
        $this->contactclientModel = $contactclientModel;
        $this->repo       = $this->contactclientModel->getRepository();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => function (Options $options) {
                    $choices = [];

                    $list = $this->repo->getContactClientList($options['data']);
                    foreach ($list as $row) {
                        $choices[$row['id']] = $row['name'];
                    }

                    //sort by language
                    ksort($choices);

                    return $choices;
                },
                'expanded'    => false,
                'multiple'    => true,
                'required'    => false,
                'empty_value' => function (Options $options) {
                    return (empty($options['choices'])) ? 'mautic.contactclient.no.contactclientitem.note' : 'mautic.core.form.chooseone';
                },
                'disabled' => function (Options $options) {
                    return empty($options['choices']);
                },
                'top_level'      => 'variant',
                'variant_parent' => null,
                'ignore_ids'     => [],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'contactclient_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}
