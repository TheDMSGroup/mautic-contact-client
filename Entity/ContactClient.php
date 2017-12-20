<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class ContactClient.
 */
class ContactClient extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $editor;

    /**
     * @var string
     */
    private $html;

    /**
     * @var string
     */
    private $htmlMode;

    /**
     * @var string
     */
    private $name;

    /**
     * @var
     */
    private $category;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $website;

    /**
     * @var string
     */
    private $style;

    /**
     * @var \DateTime
     */
    private $publishUp;

    /**
     * @var \DateTime
     */
    private $publishDown;

    /**
     * @var array()
     */
    private $properties = [];

    /**
     * @var int
     */
    private $form;

    /**
     * @var string
     */
    private $cache;

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                [
                    'message' => 'mautic.core.name.required',
                ]
            )
        );

//        $metadata->addPropertyConstraint(
//            'type',
//            new NotBlank(
//                ['message' => 'mautic.contactclient.error.select_type']
//            )
//        );
//
//        $metadata->addPropertyConstraint(
//            'style',
//            new NotBlank(
//                ['message' => 'mautic.contactclient.error.select_style']
//            )
//        );
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contactclient')
            ->setCustomRepositoryClass('MauticPlugin\MauticContactClientBundle\Entity\ContactClientRepository')
            ->addIndex(['contactclient_type'], 'contactclient_type')
            ->addIndex(['style'], 'contactclient_style')
            ->addIndex(['form_id'], 'contactclient_form');

        $builder->addIdColumns();

        $builder->addCategory();

        $builder->addNamedField('type', 'string', 'contactclient_type');

        $builder->addField('style', 'string');

        $builder->addNullableField('website', 'string');

        $builder->addPublishDates();

        $builder->addNullableField('properties', 'array');

        $builder->addNamedField('form', 'integer', 'form_id', true);

        $builder->addNullableField('cache', 'text');

        $builder->createField('htmlMode', 'string')
            ->columnName('html_mode')
            ->nullable()
            ->build();

        $builder->addNullableField('editor', 'text');

        $builder->addNullableField('html', 'text');
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'description',
                    'type',
                    'website',
                    'style',
                    'publishUp',
                    'publishDown',
                    'properties',
                    'form',
                    'htmlMode',
                    'html',
                    'editor',
                    'cache',
                ]
            )
            ->build();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     *
     * @return ContactClient
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);

        $this->description = $description;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEditor()
    {
        return $this->editor;
    }

    /**
     * @param mixed $setHtml
     *
     * @return ContactClient
     */
    public function setEditor($editor)
    {
        $this->isChanged('editor', $editor);

        $this->editor = $editor;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param mixed $setHtml
     *
     * @return ContactClient
     */
    public function setHtml($html)
    {
        $this->isChanged('html', $html);

        $this->html = $html;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHtmlMode()
    {
        return $this->htmlMode;
    }

    /**
     * @param mixed $html
     *
     * @return ContactClient
     */
    public function setHtmlMode($htmlMode)
    {
        $this->isChanged('htmlMode', $htmlMode);

        $this->htmlMode = $htmlMode;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return ContactClient
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);

        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     *
     * @return ContactClient
     */
    public function setCategory($category)
    {
        $this->isChanged('category', $category);

        $this->category = $category;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param mixed $publishUp
     *
     * @return ContactClient
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);

        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @param mixed $publishDown
     *
     * @return ContactClient
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);

        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param mixed $properties
     *
     * @return ContactClient
     */
    public function setProperties($properties)
    {
        $this->isChanged('properties', $properties);

        $this->properties = $properties;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return ContactClient
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);

        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param mixed $style
     *
     * @return ContactClient
     */
    public function setStyle($style)
    {
        $this->isChanged('style', $style);

        $this->style = $style;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param mixed $website
     *
     * @return ContactClient
     */
    public function setWebsite($website)
    {
        $this->isChanged('website', $website);

        $this->website = $website;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param mixed $form
     *
     * @return ContactClient
     */
    public function setForm($form)
    {
        if ($form instanceof Form) {
            $form = $form->getId();
        }

        $this->isChanged('form', $form);

        $this->form = $form;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param mixed $cache
     *
     * @return ContactClient
     */
    public function setCache($cache)
    {
        $this->cache = $cache;

        return $this;
    }
}
