<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'contactclient');

$header = ($entity->getId())
    ?
    $view['translator']->trans(
        'mautic.contactclient.edit',
        ['%name%' => $view['translator']->trans($entity->getName())]
    )
    :
    $view['translator']->trans('mautic.contactclient.new');
$view['slots']->set('headerTitle', $header);

echo $view['assets']->includeScript('plugins/MauticContactClientBundle/Assets/js/contactclient.js');

echo $view['assets']->includeStylesheet('https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.10.0/jquery.timepicker.min.css');
echo $view['assets']->includeStylesheet('https://cdnjs.cloudflare.com/ajax/libs/jquery.businessHours/1.0.1/jquery.businessHours.min.css');
echo $view['assets']->includeStylesheet('https://cdn.rawgit.com/heathdutton/jQuery-QueryBuilder/master/dist/css/query-builder.default.min.css');
echo $view['assets']->includeStylesheet('plugins/MauticContactClientBundle/Assets/css/contactclient.css');

echo $view['form']->start($form);
?>


<!-- start: box layout -->
<div class="box-layout">

    <!-- tab container -->
    <div class="col-md-9 bg-white height-auto bdr-l contactclient-left">
        <div class="">
            <ul class="nav nav-tabs pr-md pl-md mt-10">
                <li class="active">
                    <a href="#details" role="tab" data-toggle="tab" class="contactclient-tab">
                        <?php echo $view['translator']->trans('mautic.contactclient.form.group.details'); ?>
                    </a>
                </li>
                <li>
                    <a href="#exclusive" role="tab" data-toggle="tab" class="contactclient-tab">
                        <?php echo $view['translator']->trans('mautic.contactclient.form.group.exclusive'); ?>
                    </a>
                </li>
                <li>
                    <a href="#filter" role="tab" data-toggle="tab" class="contactclient-tab">
                        <?php echo $view['translator']->trans('mautic.contactclient.form.group.filter'); ?>
                    </a>
                </li>
                <li>
                    <a href="#limits" role="tab" data-toggle="tab" class="contactclient-tab">
                        <?php echo $view['translator']->trans('mautic.contactclient.form.group.limits'); ?>
                    </a>
                </li>
                <li class="hide payload-tab">
                    <a href="#payload" role="tab" data-toggle="tab" class="contactclient-tab">
                        <?php echo $view['translator']->trans('mautic.contactclient.form.group.payload'); ?>
                    </a>
                </li>
                <li>
                    <a href="#schedule" role="tab" data-toggle="tab" class="contactclient-tab">
                        <?php echo $view['translator']->trans('mautic.contactclient.form.group.schedule'); ?>
                    </a>
                </li>
            </ul>
        </div>
        <div class="tab-content">
            <!-- pane -->
            <div class="tab-pane fade in active bdr-rds-0 bdr-w-0" id="details">
                <div class="pa-md">
                    <div class="form-group mb-0">
                        <div class="row">
                            <div class="col-md-5">
                                <?php echo $view['form']->row($form['name']); ?>
                            </div>
                            <div class="col-md-2">
                                <?php echo $view['form']->row($form['type']); ?>
                            </div>
                            <div class="col-md-5">
                                <?php echo $view['form']->row($form['website']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo $view['form']->row($form['description']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="exclusive">
                <div class="pa-md">
                    <div class="form-group mb-0">
                        <div class="row">
                            <div class="col-sm-12">
                                <?php echo $view['form']->row($form['exclusive']); ?>
                            </div>
                        </div>
                        <hr class="mnr-md mnl-md">
                    </div>
                </div>
            </div>
            <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="filter">
                <div class="pa-md">
                    <div class="form-group mb-0">
                        <div class="row">
                            <div class="col-sm-12">
                                <?php echo $view['form']->row($form['filter']); ?>
                            </div>
                        </div>
                        <hr class="mnr-md mnl-md">
                    </div>
                </div>
            </div>
            <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="limits">
                <div class="pa-md">
                    <div class="form-group mb-0">
                        <div class="row">
                            <div class="col-sm-12">
                                <?php echo $view['form']->row($form['limits']); ?>
                            </div>
                        </div>
                        <hr class="mnr-md mnl-md">
                    </div>
                </div>
            </div>
            <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="payload">
                <div class="pa-md">
                    <div class="form-group mb-0">
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo $view['form']->row($form['api_payload']); ?>
                                <?php echo $view['form']->row($form['file_payload']); ?>
                            </div>
                        </div>
                        <hr class="mnr-md mnl-md">
                        Advanced widget to go here.
                    </div>
                </div>
            </div>
            <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="schedule">
                <div class="pa-md">
                    <div class="form-group mb-0">
                        <div class="row">
                            <div class="col-sm-12">
                                <?php echo $view['form']->row($form['schedule_timezone']); ?>
                                <?php echo $view['form']->row($form['schedule_hours']); ?>
                                <div id="contactclient_schedule_hours_widget"></div>
                                <?php echo $view['form']->row($form['schedule_exclusions']); ?>
                            </div>
                        </div>
                        <hr class="mnr-md mnl-md">
                    </div>
                </div>
            </div>
            <!--/ #pane -->
        </div>
    </div>
    <!--/ tab container -->

    <!-- container -->
    <div class="col-md-3 bg-white height-auto contactclient-right">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php
            echo $view['form']->row($form['category']);
            echo $view['form']->row($form['isPublished']);
            echo $view['form']->row($form['publishUp']);
            echo $view['form']->row($form['publishDown']);
            ?>
        </div>
    </div>
    <!--/ container -->
</div>
<!--/ box layout -->







<?php echo $view['form']->end($form); ?>