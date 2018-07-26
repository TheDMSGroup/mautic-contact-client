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

<!-- filter form -->
<form method="post" action="<?php echo $view['router']->path(
    'mautic_contactclient_transactions',
    ['objectId' => $contactClient->getId()]
); ?>" id="timeline-filters" class="panel">
    <div>
        <h4><?php echo $view['translator']->trans('mautic.contactclient.search.header'); ?></h4>
        <div class="col-xs-10 va-m">
            <div class="input-group">
                <input type="text" class="form-control bdr-w-1 search tt-input" name="search" id="search"
                       placeholder="<?php echo $view['translator']->trans(
                           'mautic.contactclient.search.placeholder'
                       ); ?>"
                       value="<?php echo $events['search']; ?>">

                <div class="input-group-btn">
                    <button type="submit" id="contactClientTimelineFilterApply" name="contactClientTimelineFilterApply"
                            class="btn btn-default btn-search btn-nospin">
                        <i class="the-icon fa fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="btn-group col-xs-2">
            <a class="btn btn-default"
               onclick="Mautic.contactClientTimelineExport(<?php echo $contactClient->getId(); ?>);">
            <span>
                <i class="fa fa-download "></i>
                <span class="hidden-xs hidden-sm">Export</span>
            </span>
            </a>
        </div>
    </div>
    <input type="hidden" name="contactClientId" id="contactClientId"
           value="<?php echo $contactClient->getId(); ?>"/>
</form>
<!-- Export button -->

<div id="transactions-table">
    <?php $view['slots']->output('_content'); ?>
</div>
