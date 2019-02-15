<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ContactClientPermissions.
 */
class ContactClientPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('items');
        $this->addExtendedPermissions('files');
        if (method_exists($this, 'addCustomPermission')) {
            $this->addCustomPermission('export', ['disable' => 1024]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'contactclient';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('contactclient', 'categories', $builder, $data);
        $this->addExtendedFormFields('contactclient', 'items', $builder, $data);
        $this->addExtendedFormFields('contactclient', 'files', $builder, $data);
        if (method_exists($this, 'addCustomFormFields')) {
            $this->addCustomFormFields($this->getName(), 'export', $builder, 'mautic.core.permissions.export', ['disable' => 'mautic.core.permissions.disable'], $data);
        }
    }
}
