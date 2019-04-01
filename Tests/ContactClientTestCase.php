<?php
namespace MauticPlugin\MauticContactClientBundle\Bundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\ApiBundle\Model\ClientModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mautic\LeadBundle\Entity\Lead;

class ContactClientTestCase extends MauticMysqlTestCase
{

    /** @var  Client $client */
    protected $client;

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


        $sqlFile = file_get_contents(__DIR__.'/contactClient_schema.sql');
        $this->applySqlFromFile($sqlFile);

        $this->client = static::createClient();
        $this->container = $this->client->getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');

        $this->clientModel = $this->container->get('mautic.contactclient.model.contactclient');

        $this->clientEntity = $this->clientModel->getEntity(1);

        parent::setUp();
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
        $now = new DateTime('now');
        $contact = new Lead();

        $contact->setDateAdded($now);
        $contact->setEmail('testClient@email.com');
        $contact->setPhone('7273330000');
        $contact->getFirstname('Test');
        $contact->setLastname('LastTest');


        return $contact;
    }
}