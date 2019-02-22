<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Helper;

use Mautic\LeadBundle\Entity\Lead as Contact;

/**
 * Class UtmSourceHelper.
 */
class UtmSourceHelper
{
    /**
     * @param Contact $contact
     *
     * @return null|string
     */
    public function getFirstUtmSource(Contact $contact)
    {
        $source = '';
        if (!empty($tags = $this->getSortedUtmTags($contact))) {
            $tag    = reset($tags);
            $source = trim($tag->getUtmSource());
        }

        return $source;
    }

    /**
     * @param Contact $contact
     *
     * @return array
     */
    public function getSortedUtmTags(Contact $contact)
    {
        $tags = [];
        if ($contact instanceof Contact) {
            $utmTags = $contact->getUtmTags();
            if ($utmTags) {
                $utmTags = $utmTags->toArray();
                /** @var \Mautic\LeadBundle\Entity\UtmTag $utmTag */
                foreach ($utmTags as $utmTag) {
                    $tags[$utmTag->getDateAdded()->getTimestamp()] = $utmTag;
                }
                ksort($tags);
            }
        }

        return $tags;
    }

    /**
     * @param Contact $contact
     *
     * @return string
     */
    public function getLastUtmSource(Contact $contact)
    {
        $source = '';
        if (!empty($tags = $this->getSortedUtmTags($contact))) {
            $tag    = end($tags);
            $source = trim($tag->getUtmSource());
        }

        return $source;
    }
}
