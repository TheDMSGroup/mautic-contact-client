<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PluginBundle\Bundle\PluginBundleBase;
use Mautic\PluginBundle\Entity\Plugin;

class MauticContactClientBundle extends PluginBundleBase
{
    /**
     * Called by PluginController::reloadAction when the addon version does not match what's installed.
     *
     * @param Plugin        $plugin
     * @param MauticFactory $factory
     * @param null          $metadata
     * @param Schema        $installedSchema
     *
     * @throws \Exception
     */
    public static function onPluginUpdate(
        Plugin $plugin,
        MauticFactory $factory,
        $metadata = null,
        Schema $installedSchema = null
    ) {
        // By default this is disabled, but for this plugin doctrine updates the schema faithfully.
        self::updatePluginSchema($metadata, $installedSchema, $factory);
    }
}
