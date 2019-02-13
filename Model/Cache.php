<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Model;

use FOS\RestBundle\Util\Codes;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Entity\Cache as CacheEntity;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientException;
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;

/**
 * Class Cache.
 */
class Cache extends AbstractCommonModel
{
    /** @var ContactClient $contactClient */
    protected $contactClient;

    /** @var Contact */
    protected $contact;

    /** @var PhoneNumberHelper */
    protected $phoneHelper;

    /** @var \Symfony\Component\DependencyInjection\Container */
    protected $container;

    /** @var string */
    protected $utmSource;

    /** @var string */
    protected $timezone;

    /** @var \DateTime */
    protected $dateSend;

    /**
     * Create all necessary cache entities for the given Contact and Contact Client.
     *
     * @throws \Exception
     */
    public function create()
    {
        $entities  = [];
        $exclusive = $this->getExclusiveRules();
        if (count($exclusive)) {
            // Create an entry for *each* exclusivity rule as they will end up with different dates of exclusivity
            // expiration. Any of these entries will suffice for duplicate checking and limit checking.
            foreach ($exclusive as $rule) {
                if (!isset($entity)) {
                    $entity = $this->createEntity();
                } else {
                    // No need to re-run all the getters and setters.
                    $entity = clone $entity;
                }
                // Each entry may have different exclusion expiration.
                $expireDate = $this->getRepository()->oldestDateAdded(
                    $rule['duration'],
                    $this->getTimezone(),
                    $this->dateSend
                );
                $entity->setExclusiveExpireDate(new \DateTime($expireDate));
                $entity->setExclusivePattern($rule['matching']);
                $entity->setExclusiveScope($rule['scope']);
                $entities[] = $entity;
            }
        } else {
            // A single entry will suffice for all duplicate checking and limit checking.
            $entities[] = $this->createEntity();
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
        $exclusive  = $jsonHelper->decodeObject($this->contactClient->getExclusive(), 'Exclusive');

        return $this->mergeRules($exclusive);
    }

    /**
     * Validate and merge the rules object (exclusivity/duplicate/limits).
     *
     * @param      $rules
     * @param bool $requireMatching
     *
     * @return array
     */
    private function mergeRules($rules, $requireMatching = true)
    {
        $newRules = [];
        if (isset($rules->rules) && is_array($rules->rules)) {
            foreach ($rules->rules as $rule) {
                // Exclusivity and Duplicates have matching, Limits may not.
                if (
                    (!$requireMatching || !empty($rule->matching))
                    && !empty($rule->scope)
                    && !empty($rule->duration)
                ) {
                    $duration = $rule->duration;
                    $scope    = intval($rule->scope);
                    $value    = isset($rule->value) ? strval($rule->value) : '';
                    $key      = $duration.'-'.$scope.'-'.$value;
                    if (!isset($newRules[$key])) {
                        $newRules[$key] = [];
                        if (!empty($rule->matching)) {
                            $newRules[$key]['matching'] = intval($rule->matching);
                        }
                        $newRules[$key]['scope']    = $scope;
                        $newRules[$key]['duration'] = $duration;
                        $newRules[$key]['value']    = $value;
                    } elseif (!empty($rule->matching)) {
                        $newRules[$key]['matching'] += intval($rule->matching);
                    }
                    if (isset($rule->quantity)) {
                        if (!isset($newRules[$key]['quantity'])) {
                            $newRules[$key]['quantity'] = intval($rule->quantity);
                        } else {
                            $newRules[$key]['quantity'] = min($newRules[$key]['quantity'], intval($rule->quantity));
                        }
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
     *
     * @throws \Exception
     */
    private function createEntity()
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
        $utmSource = $this->getUtmSource();
        if (!empty($utmSource)) {
            $entity->setUtmSource($utmSource);
        }
        if ($this->dateSend) {
            $entity->setDateAdded($this->dateSend);
        }

        return $entity;
    }

    /**
     * @param $phone
     *
     * @return string
     */
    private function phoneValidate($phone)
    {
        $result = null;
        $phone  = trim($phone);
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
     * Get the original / first utm source code for contact.
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getUtmSource()
    {
        if (!$this->utmSource) {
            $utmHelper       = $this->getContainer()->get('mautic.contactclient.helper.utmsource');
            $this->utmSource = $utmHelper->getFirstUtmSource($this->contact);
        }

        return $this->utmSource;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\Container
     */
    private function getContainer()
    {
        if (!$this->container) {
            $this->container = $this->dispatcher->getContainer();
        }

        return $this->container;
    }

    /**
     * @return \MauticPlugin\MauticContactClientBundle\Entity\CacheRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticContactClientBundle:Cache');
    }

    /**
     * Get the global timezone setting.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function getTimezone()
    {
        if (!$this->timezone) {
            $this->timezone = $this->getContainer()->get('mautic.helper.core_parameters')->getParameter(
                'default_timezone'
            );
        }

        return $this->timezone;
    }

    /**
     * Given a contact, evaluate exclusivity rules of all cache entries against it.
     *
     * @throws ContactClientException
     * @throws \Exception
     */
    public function evaluateExclusive()
    {
        if (!$this->contactClient->getExclusiveIgnore()) {
            $exclusive = $this->getRepository()->findExclusive(
                $this->contact,
                $this->contactClient,
                $this->dateSend
            );
            if ($exclusive) {
                throw new ContactClientException(
                    'Skipping exclusive Contact.',
                    Codes::HTTP_CONFLICT,
                    null,
                    Stat::TYPE_EXCLUSIVE,
                    false,
                    null,
                    $exclusive
                );
            }
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
            $this->getDuplicateRules(),
            $this->getUtmSource(),
            $this->getTimezone(),
            $this->dateSend
        );
        if ($duplicate) {
            throw new ContactClientException(
                'Skipping duplicate Contact.',
                Codes::HTTP_CONFLICT,
                null,
                Stat::TYPE_DUPLICATE,
                false,
                null,
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
        $duplicate  = $jsonHelper->decodeObject($this->contactClient->getDuplicate(), 'Duplicate');

        return $this->mergeRules($duplicate);
    }

    /**
     * Using the duplicate rules, evaluate if the current contact matches any entry in the cache.
     *
     * @throws ContactClientException
     * @throws \Exception
     */
    public function evaluateLimits()
    {
        $limits = $this->getRepository()->findLimit(
            $this->contactClient,
            $this->getLimitRules(),
            $this->getTimezone(),
            $this->dateSend
        );
        if ($limits) {
            throw new ContactClientException(
                'Not able to send contact to client due to an exceeded budget.',
                Codes::HTTP_TOO_MANY_REQUESTS,
                null,
                Stat::TYPE_LIMITS,
                false,
                null,
                $limits
            );
        }
    }

    /**
     * Given the Contact and Contact Client, get the rules used to evaluate limits.
     *
     * @throws \Exception
     */
    public function getLimitRules()
    {
        $jsonHelper = new JSONHelper();
        $limits     = $jsonHelper->decodeObject($this->contactClient->getLimits(), 'Limits');

        return $this->mergeRules($limits, false);
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
     * @param $dateSend
     *
     * @return $this
     */
    public function setDateSend($dateSend = null)
    {
        if (!$dateSend) {
            $this->dateSend = new \DateTime();
        } else {
            $this->dateSend = $dateSend;
        }

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
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setContactClient(ContactClient $contactClient)
    {
        $this->contactClient = $contactClient;

        return $this;
    }
}
