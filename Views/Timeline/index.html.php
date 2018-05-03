<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php
$orderBy          = isset($events['filters']['order']) && !empty($events['filters']['order'][0]) ? $events['filters']['order'][0] : 'date_added';
$orderByDirection = isset($events['filters']['order']) && !empty($events['filters']['order'][1]) ? $events['filters']['order'][1] : 'DESC';
$page             = isset($events['page']) && !empty($events['page']) ? $events['page'] : 1;

?>

<div class="panel-body box-layout">
    <!-- filter form -->
    <h4><?php echo $view['translator']->trans('mautic.contactclient.search.header'); ?></h4>
    <form method="post" action="<?php echo $view['router']->path(
        'mautic_contactclient_timeline_action',
        ['contactClientId' => $contactClient->getId()]
    ); ?>" id="timeline-filters">
        <div class="col-xs-8 col-lg-10 va-m form-inline">
            <div class="input-group col-xs-8">
                <input type="text" class="form-control bdr-w-1 search tt-input" name="search" id="search"
                       placeholder="<?php echo $view['translator']->trans(
                           'mautic.contactclient.search.placeholder'
                       ); ?>"
                       value="<?php echo $events['filters']['search']; ?>">

                <div class="input-group-btn">
                    <button type="submit" id="contactClientTimelineFilterApply" name="contactClientTimelineFilterApply"
                            class="btn btn-default btn-search btn-nospin">
                        <i class="the-icon fa fa-search fa-fw"></i>
                    </button>
                </div>

                <div class="input-group-btn" style="width:auto;font-size:1em;padding-left:4px;"
                ">
                    <input id="include-logs" type="checkbox"
                           title="Apply search term to verbose logs - may cause unexpected results." name="logs"
                           class="bdr-w-0">
                    <label style="padding:4px;" for="include-logs">Apply search term to verbose logs.</label>
                </div>
            </div>
        </div>


        <input type="hidden" name="contactClientId" id="contactClientId" value="<?php echo $contactClient->getId(); ?>"/>
        <input type="hidden" name="orderBy" id="orderBy" value="<?php echo $orderBy; ?>:<?php echo $orderByDirection; ?>"/>
        <input type="hidden" name="page" id="page" value="<?php echo $page; ?>"/>

    </form>
<!-- Export button -->
    <div class="std-toolbar btn-group">
        <a class="btn btn-default"
           onclick="Mautic.exportContactClientTimeline(<?php echo  $contactClient->getId(); ?>);">
            <span>
                <i class="fa fa-download "></i>
                <span class="hidden-xs hidden-sm">Export</span>
            </span>
        </a>
    </div>
</div>


<script>
    mauticLang['showMore'] = '<?php echo $view['translator']->trans('mautic.core.more.show'); ?>';
    mauticLang['hideMore'] = '<?php echo $view['translator']->trans('mautic.core.more.hide'); ?>';
</script>
<!-- Spinner -->
<div id="client-timeline-overlay">
    <div style="position: relative; left: 45%; index: 1024;display:inline-block; opacity: .5;"><i class="fa fa-spinner fa-spin fa-4x"></i>
    </div>
</div>

<div id="timeline-table">
    <?php $view['slots']->output('_content'); ?>
</div>
