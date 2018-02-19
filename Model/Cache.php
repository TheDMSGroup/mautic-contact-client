<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
// use MauticPlugin\MauticContactClientBundle\Entity\CacheRepository;
use MauticPlugin\MauticContactClientBundle\Entity\CacheRepository;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Entity\Cache as CacheEntity;
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientException;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;

/**
 * Class Cache
 * @package MauticPlugin\MauticContactClientBundle\Model
 */
class Cache extends AbstractCommonModel
{

    /** @var ContactClient $contactClient */
    protected $contactClient;

    /** @var Contact */
    protected $contact;

    /** @var PhoneNumberHelper */
    protected $phoneHelper;

    /**
     * Create all necessary cache entities for the given Contact and Contact Client.
     * @throws \Exception
     */
    public function create()
    {
        $entities = [];
        $exclusive = $this->getExclusiveRules();
        if (count($exclusive)) {
            // Create an entry for *each* exclusivity rule as they will end up with different dates of exclusivity
            // expiration. Any of these entries will suffice for duplicate checking and limit checking.
            foreach ($exclusive as $rule) {
                if (!isset($entity)) {
                    $entity = $this->new();
                } else {
                    // No need to re-run all the getters and setters.
                    $entity = clone $entity;
                }
                // Each entry may have different exclusion expiration.
                $expireDate = new \DateTime();
                $expireDate->add(new \DateInterval($rule['duration']));
                $entity->setExclusiveExpireDate($expireDate);
                $entity->setExclusivePattern($rule['matching']);
                $entity->setExclusiveScope($rule['scope']);
                $entities[] = $entity;
            }
        } else {
            // A single entry will suffice for all duplicate checking and limit checking.
            $entities[] = $this->new();
        }
        if (count($entities)) {
            $this->getRepository()->saveEntities($entities);
        }
    }

    /**
     * Given the Contact and Contact Client, discern which exclusivity entries need to be cached.
     *
     * @throws \Exception
     */
    public function getExclusiveRules()
    {
        $jsonHelper = new JSONHelper();
        $exclusive = $jsonHelper->decodeObject($this->contactClient->getExclusive(), 'Exclusive');

        return $this->mergeRules($exclusive);
    }

    /**
     * Validate and merge the rules object (exclusivity/duplicate/limits)
     *
     * @param $rules
     * @return array
     */
    private function mergeRules($rules)
    {
        $newRules = [];
        if (isset($rules->rules) && is_array($rules->rules)) {
            foreach ($rules->rules as $rule) {
                if (
                    !empty($rule->matching)
                    && !empty($rule->scope)
                    && !empty($rule->duration)
                ) {
                    $duration = $rule->duration;
                    $scope = intval($rule->scope);
                    $key = $duration.'-'.$scope;
                    if (!isset($newRules[$key])) {
                        $newRules[$key] = [];
                        $newRules[$key]['matching'] = intval($rule->matching);
                        $newRules[$key]['scope'] = $scope;
                        $newRules[$key]['duration'] = $duration;
                    } else {
                        $newRules[$key]['matching'] += intval($rule->matching);
                    }
                }
            }
        }
        krsort($newRules);

        return $newRules;
    }

    /**
     * Create a new cache entity with the existing Contact and contactClient.
     * Normalize the fields as much as possible to aid in exclusive/duplicate/limit correlation.
     *
     * @return CacheEntity
     */
    private function new()
    {
        $entity = new CacheEntity();
        $entity->setAddress1(trim(ucwords($this->contact->getAddress1())));
        $entity->setAddress2(trim(ucwords($this->contact->getAddress2())));
        $category = $this->contactClient->getCategory();
        if ($category) {
            $entity->setCategory($category->getId());
        }
        $entity->setCity(trim(ucwords($this->contact->getCity())));
        $entity->setContact($this->contact->getId());
        $entity->setContactClient($this->contactClient->getId());
        $entity->setState(trim(ucwords($this->contact->getStage())));
        $entity->setCountry(trim(ucwords($this->contact->getCountry())));
        $entity->setZipcode(trim($this->contact->getZipcode()));
        $entity->setEmail(trim($this->contact->getEmail()));
        $phone = $this->phoneValidate($this->contact->getPhone());
        if (!empty($phone)) {
            $entity->setPhone($phone);
        }
        $mobile = $this->phoneValidate($this->contact->getMobile());
        if (!empty($mobile)) {
            $entity->setMobile($mobile);
        }
        $utmTags = $this->contact->getUtmTags();
        if ($utmTags) {
            $utmTags = $utmTags->toArray();
            if (isset($utmTags[0])) {
                $utmSource = $utmTags[0]->getUtmSource();
                if (!empty($utmSource)) {
                    $entity->setUtmSource(trim($utmSource));
                }
            }
        }

        return $entity;
    }

    /**
     * @param $phone
     * @return string
     */
    private function phoneValidate($phone)
    {
        $result = null;
        $phone = trim($phone);
        if (!empty($phone)) {
            if (!$this->phoneHelper) {
                $this->phoneHelper = new PhoneNumberHelper();
            }
            try {
                $phone = $this->phoneHelper->format($phone);
                if (!empty($phone)) {
                    $result = $phone;
                }
            } catch (\Exception $e) {
            }
        }

        return $result;
    }

    /**
     * @return \MauticPlugin\MauticContactClientBundle\Entity\CacheRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticContactClientBundle:Cache');
    }

    /**
     * Given a contact, evaluate exclusivity rules of all cache entries against it.
     *
     * @throws ContactClientException
     * @throws \Exception
     */
    public function evaluateExclusive()
    {
        $exclusive = $this->getRepository()->findExclusive(
            $this->contact,
            $this->contactClient
        );
        if ($exclusive) {
            throw new ContactClientException(
                'Skipping exclusive Contact.'.
                json_encode($exclusive),
                0,
                null,
                Stat::TYPE_EXCLUSIVE,
                false,
                $exclusive
            );
        }
    }

    /**
     * Using the duplicate rules, evaluate if the current contact matches any entry in the cache.
     *
     * @throws ContactClientException
     * @throws \Exception
     */
    public function evaluateDuplicate()
    {
        $duplicate = $this->getRepository()->findDuplicate(
            $this->contact,
            $this->contactClient,
            $this->getDuplicateRules()
        );
        if ($duplicate) {
            throw new ContactClientException(
                'Skipping duplicate Contact.',
                0,
                null,
                Stat::TYPE_DUPLICATE,
                false,
                $duplicate
            );
        }
    }

    /**
     * Given the Contact and Contact Client, get the rules used to evaluate duplicates.
     *
     * @throws \Exception
     */
    public function getDuplicateRules()
    {
        $jsonHelper = new JSONHelper();
        $duplicate = $jsonHelper->decodeObject($this->contactClient->getDuplicate(), 'Duplicate');

        return $this->mergeRules($duplicate);
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param Contact $contact
     *
     * @return $this
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return ContactClient
     */
    public function getContactClient()
    {
        return $this->contactClient;
    }

    /**
     * @param ContactClient $contactClient
     * @return $this
     * @throws \Exception
     */
    public function setContactClient(ContactClient $contactClient)
    {
        $this->contactClient = $contactClient;

        return $this;
    }

}