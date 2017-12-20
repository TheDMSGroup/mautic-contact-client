<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticContactClientBundle:ContactClient:index.html.php');
}
?>

<?php if (count($items)): ?>
    <div class="table-responsive page-list">
        <table class="table table-hover table-striped table-bordered contactclient-list" id="contactclientTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#contactclientTable',
                        'routeBase'       => 'contactclient',
                        'templateButtons' => [
                            'delete' => $permissions['plugin:contactclient:items:delete'],
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'contactclient',
                        'orderBy'    => 'f.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-contactclient-name',
                        'default'    => true,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'contactclient',
                        'orderBy'    => 'c.title',
                        'text'       => 'mautic.core.category',
                        'class'      => 'visible-md visible-lg col-contactclient-category',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'contactclient',
                        'orderBy'    => 'f.type',
                        'text'       => 'mautic.contactclient.thead.type',
                        'class'      => 'visible-md visible-lg col-contactclient-type',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'contactclient',
                        'orderBy'    => 'f.style',
                        'text'       => 'mautic.contactclient.thead.style',
                        'class'      => 'visible-md visible-lg col-contactclient-style',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'contactclient',
                        'orderBy'    => 'f.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-contactclient-id',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => [
                                    'edit' => $view['security']->hasEntityAccess(
                                        $permissions['plugin:contactclient:items:editown'],
                                        $permissions['plugin:contactclient:items:editother'],
                                        $item->getCreatedBy()
                                    ),
                                    'clone'  => $permissions['plugin:contactclient:items:create'],
                                    'delete' => $view['security']->hasEntityAccess(
                                        $permissions['plugin:contactclient:items:deleteown'],
                                        $permissions['plugin:contactclient:items:deleteother'],
                                        $item->getCreatedBy()
                                    ),
                                ],
                                'routeBase' => 'contactclient',
                            ]
                        );
                        ?>
                    </td>
                    <td>
                        <div>
                            <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_icon.html.php', ['item' => $item, 'model' => 'contactclient']); ?>
                            <a data-toggle="ajax" href="<?php echo $view['router']->path(
                                'mautic_contactclient_action',
                                ['objectId' => $item->getId(), 'objectAction' => 'view']
                            ); ?>">
                                <?php echo $item->getName(); ?>
                            </a>
                        </div>
                        <?php if ($description = $item->getDescription()): ?>
                            <div class="text-muted mt-4">
                                <small><?php echo $description; ?></small>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="visible-md visible-lg">
                        <?php $category = $item->getCategory(); ?>
                        <?php $catName  = ($category) ? $category->getTitle() : $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                        <?php $color    = ($category) ? '#'.$category->getColor() : 'inherit'; ?>
                        <span style="white-space: nowrap;"><span class="label label-default pa-4" style="border: 1px solid #d5d5d5; background: <?php echo $color; ?>;"> </span> <span><?php echo $catName; ?></span></span>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $view['translator']->trans('mautic.contactclient.type.'.$item->getType()); ?></td>
                    <td class="visible-md visible-lg"><?php echo $view['translator']->trans('mautic.contactclient.style.'.$item->getStyle()); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <?php echo $view->render(
            'MauticCoreBundle:Helper:pagination.html.php',
            [
                'totalItems' => count($items),
                'page'       => $page,
                'limit'      => $limit,
                'baseUrl'    => $view['router']->path('mautic_contactclient_index'),
                'sessionVar' => 'contactclient',
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => 'mautic.contactclient.noresults.tip']); ?>
<?php endif; ?>
