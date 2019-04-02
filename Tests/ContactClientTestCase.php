<?php

namespace MauticPlugin\MauticContactClientBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\ApiBundle\Model\ClientModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\UtmTag;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mautic\LeadBundle\Entity\Lead;

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
        $this->clientEntity = $this->clientModel->getEntity(1);

    }

    public function getClientModel()
    {
        return $this->clientModel;
    }

    public function getClientEntity()
    {
        return $this->clientEntity;
    }

    public function getContact()
    {
        $now = new \DateTime('now');
        $contact = new Lead();

        $contact->setDateAdded($now);
        $contact->setEmail('testClient@email.com');
        $contact->setPhone('7273330000');
        $contact->getFirstname('Test');
        $contact->setLastname('LastTest');

        $utmTag = new UtmTag();
        $utmTag->setLead($contact);
        $utmTag->setUtmSource('10101UTM');
        $utmTag->setDateAdded(new \DateTime());
        $contact->setUtmTags($utmTag);


        return $contact;
    }
}