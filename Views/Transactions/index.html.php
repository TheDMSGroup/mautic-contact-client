<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
$view['slots']
<div class="panel-body box-layout">
    <!-- filter form -->
    <h4><?php echo $view['translator']->trans('mautic.contactclient.search.header'); ?></h4>
    <form method="post" action="<?php echo $view['router']->path(
        'mautic_contactclienttransactions_index',
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
            </div>
        </div>


        <input type="hidden" name="contactClientId" id="contactClientId" value="<?php echo $contactClient->getId(); ?>"/>
        <input type="hidden" name="orderBy" id="orderBy" value="<?php echo $orderBy; ?>:<?php echo $orderByDirection; ?>"/>
        <input type="hidden" name="page" id="page" value="<?php echo $page; ?>"/>

    </form>
<!-- Export button -->
    <div class="std-toolbar btn-group">
        <a class="btn btn-default"
           onclick="Mautic.contactClientTimelineExport(<?php echo $contactClient->getId(); ?>);">
            <span>
                <i class="fa fa-download "></i>
                <span class="hidden-xs hidden-sm">Export</span>
            </span>
        </a>
    </div>
</div>

<div id="transactions-table">
    <?php $view['slots']->output('_content'); ?>
</div>
