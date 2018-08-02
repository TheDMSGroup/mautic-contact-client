<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'contactclient');
$view['slots']->set('headerTitle', $item->getName());

echo $view['assets']->includeScript('plugins/MauticContactClientBundle/Assets/build/contactclient.min.js', 'contactclientOnLoad', 'contactclientOnLoad');
echo $view['assets']->includeStylesheet('plugins/MauticContactClientBundle/Assets/build/contactclient.min.css');

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item'            => $item,
            'templateButtons' => [
                'edit'   => $view['security']->hasEntityAccess(
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
                'close'  => $view['security']->isGranted('plugin:contactclient:items:view'),
            ],
            'routeBase'       => 'contactclient',
            'langVar'         => 'mautic.contactclient',
        ]
    )
);

$website = $item->getWebsite();

?>
<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- form detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10">
                        <div class="text-muted"><?php echo $item->getDescription(); ?></div>
                    </div>
                    <div class="col-xs-2 text-right">
                        <?php echo $view->render(
                            'MauticCoreBundle:Helper:publishstatus_badge.html.php',
                            ['entity' => $item]
                        ); ?>
                    </div>
                </div>
            </div>
            <!--/ form detail header -->

            <!-- form detail collapseable -->
            <div class="collapse" id="contactclient-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:details.html.php',
                                ['entity' => $item]
                            ); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ form detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- form detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.details'); ?>">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#contactclient-details"><span
                                class="caret"></span> <?php echo $view['translator']->trans(
                            'mautic.core.details'
                        ); ?></a>
                </span>
            </div>
            <!--/ form detail collapseable toggler -->

            <!-- stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-xs-4 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-line-chart"></span>
                                        <?php echo $view['translator']->trans('mautic.contactclient.graph.stats'); ?>
                                    </h5>
                                </div>
                                <div class="col-xs-8 va-m">
                                    <?php echo $view->render(
                                        'MauticContactClientBundle:Helper:graph_chartfilter.html.php',
                                        ['chartFilterForm' => $chartFilterForm, 'class' => 'pull-right']
                                    ); ?>
                                </div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <?php echo $view->render(
                                    'MauticCoreBundle:Helper:chart.html.php',
                                    ['chartData' => $stats, 'chartType' => 'line', 'chartHeight' => 300]
                                ); ?>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <?php echo $view->render(
                                    'MauticContactClientBundle:ContactClient:datatable.html.php',
                                    ['tableData' => $tableData]
                                ); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ stats -->

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md mt-10">
                <li class="active">
                    <a href="#transactions-container" role="tab" data-toggle="tab">
                        <span class="label label-primary mr-sm" id="TransactionsCount">
                            <?php echo $transactions['total']; ?>
                        </span>
                        <?php echo $view['translator']->trans('mautic.contactclient.transactions.events'); ?>
                    </a>
                </li>
                <?php if ($files['total']): ?>
                    <li class="">
                        <a href="#files-container" role="tab" data-toggle="tab">
                    <span class="label label-primary mr-sm" id="FileCount">
                        <?php echo $files['total']; ?>
                    </span>
                            <?php echo $view['translator']->trans('mautic.contactclient.transactions.files'); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php echo $view['content']->getCustomContent('tabs', $mauticTemplateVars); ?>
            </ul>
            <!--/ tabs controls -->

            <!-- start: tab-content -->
            <div class="tab-content pl-md pr-md pb-md">
                <div class="tab-pane fade in active bdr-w-0" id="transactions-container">
                    <?php echo $view->render(
                        'MauticContactClientBundle:Transactions:list.html.php',
                        [
                            'contactClient' => $item,
                            'transactions'  => $transactions,
                            'order'         => $order,
                            'search'        => $search,
                            'tmpl'          => 'index',
                        ]
                    ); ?>
                </div>
                <?php if ($files['total']): ?>
                    <div class="tab-pane fade in bdr-w-0" id="files-container">
                        <?php echo $view->render(
                            'MauticContactClientBundle:Files:list.html.php',
                            [
                                'contactClient' => $item,
                                'files'         => $files,
                                'order'         => $order,
                                'tmpl'          => 'index'
                            ]
                        ); ?>
                    </div>
                <?php endif; ?>

                <!-- custom content -->
                <?php echo $view['content']->getCustomContent('tabs.content', $mauticTemplateVars); ?>
                <!-- end: custom content -->

            </div>
            <!--/ end: tab-content -->
        </div>
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- form HTML -->
        <?php if ($website): ?>
            <div class="pa-md">
                <div class="panel bg-info bg-light-lg bdr-w-0 mb-0">
                    <div class="panel-body">
                        <h5 class="fw-sb mb-sm"><?php echo $view['translator']->trans(
                                'mautic.contactclient.form.website'
                            ); ?></h5>
                        <p class="mb-sm"><a href="<?php echo $website; ?>" target="_blank"><?php echo $website; ?></a>
                        </p>
                    </div>
                </div>
            </div>

            <hr class="hr-w-2" style="width:50%">
        <?php endif; ?>
        <!--/ form HTML -->

        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mb-0">

            <!-- recent activity -->
            <?php echo $view->render(
                'MauticCoreBundle:Helper:recentactivity.html.php',
                ['logs' => $auditlog['events']]
            ); ?>

        </div>
    </div>
    <!--/ right section -->
</div>
<!--/ end: box layout -->

<input type="hidden" name="entityId" id="entityId" value="<?php echo $item->getId(); ?>"/>
