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

$header = ($entity->getId())
    ?
    $view['translator']->trans(
        'mautic.contactclient.edit',
        ['%name%' => $view['translator']->trans($entity->getName())]
    )
    :
    $view['translator']->trans('mautic.contactclient.new');
$view['slots']->set('headerTitle', $header);

echo $view['assets']->includeScript('plugins/MauticContactClientBundle/Assets/build/contactclient.min.js?v=2', 'contactclientOnLoad', 'contactclientOnLoad');
echo $view['assets']->includeStylesheet('plugins/MauticContactClientBundle/Assets/build/contactclient.min.css');
echo $view['assets']->includeStylesheet('https://fonts.googleapis.com/css?family=Roboto+Mono');
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
                            <i class="fa fa-cog fa-lg pull-left"></i><?php echo $view['translator']->trans(
                                'mautic.contactclient.form.group.details'
                            ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#dnc" role="tab" data-toggle="tab" class="contactclient-tab">
                            <i class="fa fa-exclamation-triangle fa-lg pull-left"></i><?php echo $view['translator']->trans(
                                'mautic.contactclient.form.group.dnc'
                            ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#duplicate" role="tab" data-toggle="tab" class="contactclient-tab">
                            <i class="fa fa-window-restore fa-lg pull-left"></i><?php echo $view['translator']->trans(
                                'mautic.contactclient.form.group.duplicate'
                            ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#exclusive" role="tab" data-toggle="tab" class="contactclient-tab">
                            <i class="fa fa-user-secret fa-lg pull-left"></i><?php echo $view['translator']->trans(
                                'mautic.contactclient.form.group.exclusive'
                            ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#filter" role="tab" data-toggle="tab" class="contactclient-tab">
                            <i class="fa fa-filter fa-lg pull-left"></i><?php echo $view['translator']->trans(
                                'mautic.contactclient.form.group.filter'
                            ); ?>
                        </a>
                    </li>
                    <li id="payload-tab">
                        <a href="#payload" role="tab" data-toggle="tab" class="contactclient-tab">
                            <i class="fa fa-paper-plane fa-lg pull-left"></i><?php echo $view['translator']->trans(
                                'mautic.contactclient.form.group.payload'
                            ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#attribution" role="tab" data-toggle="tab" class="contactclient-tab">
                            <i class="fa fa-calculator fa-lg pull-left"></i><?php echo $view['translator']->trans(
                                'mautic.contactclient.form.group.attribution'
                            ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#schedule" role="tab" data-toggle="tab" class="contactclient-tab">
                            <i class="fa fa-calendar-check-o fa-lg pull-left"></i><?php echo $view['translator']->trans(
                                'mautic.contactclient.form.group.schedule'
                            ); ?>
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
                <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="attribution">
                    <div class="pa-md">
                        <div class="form-group mb-0">
                            <div class="row">
                                <div class="col-sm-6">
                                    <?php echo $view['form']->row($form['attribution_default']); ?>
                                    <div id="contactclient_attribution_settings">
                                        <?php echo $view['form']->row($form['attribution_settings']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <?php echo $view['form']->row($form['limits']); ?>
                                    <?php echo $view['form']->row($form['limits_queue']); ?>
                                </div>
                            </div>
                            <hr class="mnr-md mnl-md">
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="dnc">
                    <div class="pa-md">
                        <div class="form-group mb-0">
                            <div class="row">
                                <div class="col-sm-12">
                                    <?php echo $view['form']->row($form['dnc_checks']); ?>
                                </div>
                            </div>
                            <hr class="mnr-md mnl-md">
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="duplicate">
                    <div class="pa-md">
                        <div class="form-group mb-0">
                            <div class="row">
                                <div class="col-sm-12">
                                    <?php echo $view['form']->row($form['duplicate']); ?>
                                </div>
                            </div>
                            <hr class="mnr-md mnl-md">
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="exclusive">
                    <div class="pa-md">
                        <div class="form-group mb-0">
                            <div class="row">
                                <div class="col-sm-12">
                                    <?php echo $view['form']->row($form['exclusive']); ?>
                                    <?php echo $view['form']->row($form['exclusive_ignore']); ?>
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
                <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="payload">
                    <div class="pa-md">
                        <div class="form-group mb-0">
                            <div class="row api_payload hide">
                                <div class="col-md-12">
                                    <div id="api_payload_buttons" class="toolbar-form-buttons pull-right hide"
                                         data-spy="affix" data-offset-top="173">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default" id="api_payload_test"
                                                    data-toggle="button" aria-pressed="false" autocomplete="off">
                                                <i class="fa fa-check-circle text-success"></i>
                                                <?php echo $view['translator']->trans(
                                                    'mautic.contactclient.form.test'
                                                ); ?>
                                            </button>
                                        </div>
                                        <span class="mr-20"></span>
                                        <div class="btn-group hidden-xs view_modes">
                                            <button type="button" class="btn btn-default btn-nospin active btn-success"
                                                    id="api_payload_simple" data-toggle="button" aria-pressed="false"
                                                    autocomplete="off">
                                                <i class="fa fa-low-vision"></i>
                                                <?php echo $view['translator']->trans(
                                                    'mautic.contactclient.form.simple'
                                                ); ?>
                                            </button>
                                            <button type="button" class="btn btn-default btn-nospin"
                                                    id="api_payload_advanced" data-toggle="button" aria-pressed="false"
                                                    autocomplete="off">
                                                <i class="fa fa-eye"></i>
                                                <?php echo $view['translator']->trans(
                                                    'mautic.contactclient.form.advanced'
                                                ); ?>
                                            </button>
                                            <button type="button" class="btn btn-default btn-nospin"
                                                    id="api_payload_code" data-toggle="button" aria-pressed="false"
                                                    autocomplete="off">
                                                <i class="fa fa-code"></i>
                                                <?php echo $view['translator']->trans(
                                                    'mautic.contactclient.form.code'
                                                ); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <?php echo $view['form']->row($form['api_payload']); ?>
                                    <div id="api_payload_test_result" class="hide modal modal-xl fade bg-white" style="left: auto !important;" tabindex="-1" role="dialog" aria-labelledby="testResultsModalTitle">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <h4 class="modal-title" id="testResultsModalTitle">
                                                <?php echo $view['translator']->trans(
                                                    'mautic.contactclient.form.test_results'
                                                ); ?>
                                            </h4>
                                        </div>
                                        <div class="modal-body modal-md" role="document">
                                            <div class="row">
                                                <div class="col-md-10 col-md-offset-1 mt-20 mb-10">
                                                    <h3 id="api_payload_test_result_message" class="hide"></h3>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-10 col-md-offset-1 mt-10 mb-10">
                                                    <div id="api_payload_test_result_error" class="hide pl-20 text-danger"></div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-10 col-md-offset-1 mt-10 mb-10">
                                                    <label>
                                                        <?php echo $view['translator']->trans(
                                                            'mautic.contactclient.form.test_results.logs'
                                                        ); ?>
                                                    </label>
                                                </div>
                                                <div class="col-md-10 col-md-offset-1">
                                                    <div id="api_payload_test_result_yaml"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer hide">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row file_payload hide">
                                <div class="col-md-12">
                                    <div id="file_payload_buttons" class="toolbar-form-buttons pull-right hide"
                                         data-spy="affix" data-offset-top="173">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default" id="file_payload_test"
                                                    data-toggle="button" aria-pressed="false" autocomplete="off">
                                                <i class="fa fa-check-circle text-success"></i>
                                                <?php echo $view['translator']->trans(
                                                    'mautic.contactclient.form.test'
                                                ); ?>
                                            </button>
                                        </div>
                                        <span class="mr-20"></span>
                                        <div class="btn-group hidden-xs view_modes">
                                            <button type="button" class="btn btn-default btn-nospin active btn-success"
                                                    id="file_payload_simple" data-toggle="button" aria-pressed="false"
                                                    autocomplete="off">
                                                <i class="fa fa-low-vision"></i>
                                                <?php echo $view['translator']->trans(
                                                    'mautic.contactclient.form.simple'
                                                ); ?>
                                            </button>
                                            <button type="button" class="btn btn-default btn-nospin hide"
                                                    id="file_payload_advanced" data-toggle="button" aria-pressed="false"
                                                    autocomplete="off">
                                                <i class="fa fa-eye"></i>
                                                <?php echo $view['translator']->trans(
                                                    'mautic.contactclient.form.advanced'
                                                ); ?>
                                            </button>
                                            <button type="button" class="btn btn-default btn-nospin"
                                                    id="file_payload_code" data-toggle="button" aria-pressed="false"
                                                    autocomplete="off">
                                                <i class="fa fa-code"></i>
                                                <?php echo $view['translator']->trans(
                                                    'mautic.contactclient.form.code'
                                                ); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <?php echo $view['form']->row($form['file_payload']); ?>
                                    <div id="file_payload_test_result" class="hide modal-xl fade bg-white" tabindex="-1" role="dialog" aria-labelledby="testResultsModalTitle">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <h4 class="modal-title" id="testResultsModalTitle">
                                                <?php echo $view['translator']->trans(
                                                    'mautic.contactclient.form.test_results'
                                                ); ?>
                                            </h4>
                                        </div>
                                        <div class="modal-body modal-md" role="document">
                                            <div class="row">
                                                <div class="col-md-10 col-md-offset-1 mt-20 mb-10">
                                                    <h3 id="file_payload_test_result_message" class="hide"></h3>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-10 col-md-offset-1 mt-10 mb-10">
                                                    <div id="file_payload_test_result_error" class="hide pl-20 text-danger"></div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-10 col-md-offset-1 mt-10 mb-10">
                                                    <label>
                                                        <?php echo $view['translator']->trans(
                                                            'mautic.contactclient.form.test_results.logs'
                                                        ); ?>
                                                    </label>
                                                </div>
                                                <div class="col-md-10 col-md-offset-1">
                                                    <div id="file_payload_test_result_yaml"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer hide">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="schedule">
                    <div class="pa-md">
                        <div class="form-group mb-0">
                            <div class="row">
                                <div class="col-md-4">
                                    <?php echo $view['form']->row($form['schedule_timezone']); ?>
                                </div>
                                <div class="col-sm-12">
                                    <?php echo $view['form']->row($form['schedule_hours']); ?>
                                    <div id="contactclient_schedule_hours_widget"></div>
                                </div>
                                <div class="col-sm-12">
                                    <?php echo $view['form']->row($form['schedule_exclusions']); ?>
                                    <?php echo $view['form']->row($form['schedule_queue']); ?>
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