<?php

namespace MauticPlugin\MauticContactClientBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\ApiBundle\Model\ClientModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\UtmTag;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;

class ContactClientTestCase extends MauticMysqlTestCase
{

    /** @var  Client $appClient */
    protected $appClient;

    /** @var  ContainerInterface $container */
    protected $container;

    /** @var  EntityManager $entityManager */
    protected $entityManager;

    /** @var ClientModel $clientModel */
    protected $clientModel;

    /** @var LeadModel $leadModel */
    protected $leadModel;

    /** @var ContactClient $client */
    protected $clientEntity;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->appClient = static::createClient();
        $this->container = $this->appClient->getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
        $this->runCommand('mautic:plugins:install');

        $sqlFile = __DIR__.'/contactClient_schema.sql';
        $this->applySqlFromFile($sqlFile);

        $this->clientModel = $this->container->get('mautic.contactclient.model.contactclient');
        $this->leadModel = $this->container->get('mautic.lead.model.lead');

    }

    public function getClientModel()
    {
        return $this->clientModel;
    }

    /**
     * @param $id
     *
     * @return \Mautic\ApiBundle\Entity\oAuth1\Consumer|\Mautic\ApiBundle\Entity\oAuth2\Client|null
     */
    public function getClientEntity($id)
    {
        $this->clientEntity = $this->clientModel->getEntity($id);
        return $this->clientEntity;
    }

    public function getContact()
    {
        $now = new \DateTime('now');
        $contact = new Lead();

        $contact->setDateAdded($now);
        $contact->setEmail('testClient@email.com');
        $contact->setPhone('7273330000');
        $contact->setFirstname('FirstTest');
        $contact->setLastname('LastTest');

        $utmTag = new UtmTag();
        $utmTag->setLead($contact);
        $utmTag->setUtmSource('10101UTM');
        $utmTag->setDateAdded(new \DateTime());
        $contact->setUtmTags($utmTag);

        $fields = [
            'core' => [
                'firstname' => [
                    'alias' => 'firstname',
                    'label' => 'First Name',
                    'type'  => 'text',
                    'value' => 'FirstTest',
                ],
                'lastname' => [
                    'alias' => 'lastname',
                    'label' => 'Last Name',
                    'type'  => 'text',
                    'value' => 'LastTest',
                ],
                'phone' => [
                    'alias' => 'phone',
                    'label' => 'Phone',
                    'type'  => 'text',
                    'value' => '7273330000',
                ],
                'email' => [
                    'alias' => 'email',
                    'label' => 'Email',
                    'type'  => 'text',
                    'value' => 'testClient@email.com',
                ],
            ],
        ];
        $contact->setFields($fields);



        return $contact;
    }
}