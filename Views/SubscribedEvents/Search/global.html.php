<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<?php if (!empty($showMore)): ?>
    <a href="<?php echo $view['router']->generate('mautic_contactclient_index', ['search' => $searchString]); ?>"
       data-toggle="ajax">
        <span><?php echo $view['translator']->trans('mautic.core.search.more', ['%count%' => $remaining]); ?></span>
    </a>
<?php else: ?>
    <a href="<?php echo $view['router']->generate(
        'mautic_contactclient_action',
        ['objectAction' => 'view', 'objectId' => $client->getId()]
    ); ?>" data-toggle="ajax">
        <span><?php echo $client->getName(); ?></span>
        <?php
        ?>
        <span class="label label-default pull-right" data-toggle="tooltip" data-placement="left"
              title="ID: <?php echo $client->getId(); ?>" ; ?>ID: <?php echo $client->getId(); ?></span>
    </a>
    <div class="clearfix"></div>
<?php endif; ?>