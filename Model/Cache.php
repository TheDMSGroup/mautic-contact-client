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
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Entity\Cache as CacheEntity;
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;

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
            foreach ($exclusive as $duration => $rule) {
                if (!isset($entity)) {
                    $entity = $this->new();
                } else {
                    // No need to re-run all the getters and setters.
                    $entity = clone $entity;
                }
                // Each entry may have different exclusion expiration.
                $expireDate = new \DateTime();
                $expireDate->add(new \DateInterval($duration));
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
            $cacheRepository = $this->getCacheRepository();
            $cacheRepository->saveEntities($entities);
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
                    if (!isset($newRules[$duration])) {
                        $newRules[$duration] = [];
                        $newRules[$duration]['matching'] = intval($rule->matching);
                        $newRules[$duration]['scope'] = intval($rule->scope);
                    } else {
                        $newRules[$duration]['matching'] += intval($rule->matching);
                        $newRules[$duration]['scope'] += intval($rule->scope);
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
        $phoneHelper = new PhoneNumberHelper();
        $phone = trim($this->contact->getPhone());
        if (!empty($phone)) {
            try {
                $entity->setPhone($phoneHelper->format($phone));
            } catch (\Exception $e) {
            }
        }
        $mobile = trim($this->contact->getMobile());
        if (!empty($mobile)) {
            try {
                $entity->setMobile($phoneHelper->format($mobile));
            } catch (\Exception $e) {
            }
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
     * @return bool|\Doctrine\ORM\EntityRepository|\Mautic\CoreBundle\Entity\CommonRepository
     */
//    public function getRepository()
//    {
//        return $this->em->getRepository('MauticContactClientBundle:Cache');
//    }


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