<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
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
echo $view['assets']->includeStylesheet('plugins/MauticContactClientBundle/Assets/css/contactclient.css');

echo $view['form']->start($form);
?>
    <!-- start: box layout -->
    <div class="box-layout">
        <!-- container -->
        <div class="col-md-9 bg-auto height-auto bdr-r pa-md">
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
            <div class="row">
                <div class="col-md-12">
                    <?php echo $view['form']->row($form['specification']); ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 bg-white height-auto">
            <div class="pr-lg pl-lg pt-md pb-md">
                <?php
                echo $view['form']->row($form['category']);
                echo $view['form']->row($form['isPublished']);
                echo $view['form']->row($form['publishUp']);
                echo $view['form']->row($form['publishDown']);
                ?>
                <hr />
            </div>
        </div>
    </div>

    <div class="hide builder contactclient-builder">
        <div class="builder-panel builder-panel-contactclient">
            <div class="builder-panel-top">
                <p>
                    <button type="button" class="btn btn-primary btn-close-builder btn-block" onclick="Mautic.closeContactClientBuilder(this);"><?php echo $view['translator']->trans(
                            'mautic.core.close.builder'
                        ); ?></button>
                </p>
            </div>
            <?php
            $class = ($form['type']->vars['data']) ? 'contactclient-type-'.$form['type']->vars['data'] : 'contactclient-type-all';
            ?>
            <div class="<?php echo $class; ?>" style="margin-top: 40px;" id="contactclientFormContent">
                <!-- start contactclient type  -->
                <div class="panel panel-default" id="contactclientType">
                    <div class="panel-heading">
                        <h4 class="contactclient-type-header panel-title">
                            <a role="button" data-toggle="collapse" href="#contactclientTypePanel" aria-expanded="true" aria-controls="contactclientTypePanel">
                                <i class="fa fa-bullseye"></i> <?php echo $view['translator']->trans('mautic.contactclient.form.type'); ?>
                            </a>
                        </h4>
                    </div>
                    <div id="contactclientTypePanel" class="panel-collapse collapse in" role="tabpanel">
                        <ul class="list-group mb-0">
                            <li data-contactclient-type="form" class="contactclient-type list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="fa fa-2x fa-pencil-square-o text-primary"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.contactclient.form.type.form'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.contactclient.form.type.form_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>

                            <li class="contactclient-properties contactclient-form-properties list-group-item pl-sm pr-sm" style="display: none;"></li>

                            <li data-contactclient-type="notice" class="contactclient-type list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="fa fa-2x fa-bullhorn text-warning"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.contactclient.form.type.notice'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.contactclient.form.type.notice_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>

                            <li class="contactclient-properties contactclient-notice-properties list-group-item pl-sm pr-sm" style="display: none;"></li>

                            <li data-contactclient-type="link" class="contactclient-type list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="fa fa-2x fa-hand-o-right text-info"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.contactclient.form.type.link'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.contactclient.form.type.link_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>

                            <li class="contactclient-properties contactclient-link-properties list-group-item pl-sm pr-sm" style="display: none;"></li>
                        </ul>
                    </div>
                </div>
                <!-- end contactclient type -->

                <!-- start contactclient type tab -->
                <div class="panel panel-default" id="contactclientStyle">
                    <div class="panel-heading">
                        <h4 class="panel-title contactclient-style-header">
                            <a role="button" data-toggle="collapse" href="#contactclientStylePanel" aria-expanded="true" aria-controls="contactclientStylePanel">
                                <i class="fa fa-desktop"></i> <?php echo $view['translator']->trans('mautic.contactclient.form.style'); ?>
                            </a>
                        </h4>
                    </div>
                    <div id="contactclientStylePanel" class="panel-collapse collapse" role="tabpanel">
                        <ul class="list-group mb-0">
                            <li data-contactclient-style="bar" class="contactclient-style visible-contactclient-style-bar list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="pl-2 fa fa-2x fa-minus text-primary"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.contactclient.style.bar'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.contactclient.style.bar_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>
                            <li class="contactclient-properties contactclient-bar-properties list-group-item pl-sm pr-sm" style="display: none;"></li>

                            <li data-contactclient-style="modal" class="contactclient-style visible-contactclient-style-modal list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="fa fa-2x fa-list-alt text-warning"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.contactclient.style.modal'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.contactclient.style.modal_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>
                            <li class="contactclient-properties contactclient-modal-properties list-group-item pl-sm pr-sm" style="display: none;"></li>

                            <li data-contactclient-style="notification" class="contactclient-style visible-contactclient-style-notification list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="pl-2 fa fa-2x fa-info-circle text-info"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans(
                                                'mautic.contactclient.style.notification'
                                            ); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.contactclient.style.notification_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>
                            <li class="contactclient-properties contactclient-notification-properties list-group-item pl-sm pr-sm" style="display: none;"></li>

                            <li data-contactclient-style="page" class="contactclient-style visible-contactclient-style-page list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="pl-2 fa fa-2x fa-square text-danger"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.contactclient.style.page'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.contactclient.style.page_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>
                            <!-- <li class="contactclient-properties contactclient-page-properties list-group-item pl-sm pr-sm" style="display: none;"></li> -->
                        </ul>
                    </div>
                </div>
                <!-- end contactclient style -->

                <!-- start contactclient colors -->
                <div class="panel panel-default" id="contactclientColors">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" href="#contactclientColorsPanel" aria-expanded="true" aria-controls="contactclientColorsPanel">
                                <i class="fa fa-paint-brush"></i> <?php echo $view['translator']->trans('mautic.contactclient.tab.contactclient_colors'); ?>
                            </a>
                        </h4>
                    </div>
                </div>
                <!-- end contactclient colors -->

                <!-- start contactclient content -->
                <div class="panel panel-default" id="contactclientContent">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" href="#contactclientContentPanel" aria-expanded="true" aria-controls="contactclientContentPanel">
                                <i class="fa fa-newspaper-o"></i> <?php echo $view['translator']->trans('mautic.contactclient.tab.contactclient_content'); ?>
                            </a>
                        </h4>
                    </div>
                    <div id="contactclientContentPanel" class="panel-collapse collapse" role="tabpanel">
                        <div class="panel-body pa-xs">

                            <!-- form type properties -->
                            <div class="contactclient-hide visible-contactclient-type-form">
                                <div class="col-sm-12" id="contactclientFormAlert">
                                    <div class="alert alert-info">
                                        <?php echo $view['translator']->trans('mautic.contactclient.form_token.instructions'); ?>
                                    </div>
                                </div>
<!--                                --><?php //echo $view['form']->row($form['form']); ?>
                                <div style="margin-bottom: 50px;"></div>
                            </div>

                            <!-- link type properties -->
                        </div>
                    </div>
                </div>
                <!-- end contactclient content -->

            </div>
        </div>
    </div>

<?php echo $view['form']->end($form); ?>