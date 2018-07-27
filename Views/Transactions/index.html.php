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

<div class="bg-white panel pt-md pb-md">
    <!-- filter form -->
    <form method="post" id="transactions-filters" data-toggle="ajax"
          action="<?php echo $view['router']->path(
        'mautic_contactclient_action',
        ['objectId' => $contactClient->getId(), 'objectAction' => 'view']
    ); ?>">
        <div class="col-xs-1 va-m">
            <h4><?php echo $view['translator']->trans('mautic.contactclient.search.header'); ?></h4>
        </div>
        <div class="col-xs-8 va-m">
            <div class="input-group">
                <input type="text" class="form-control bdr-w-0 search tt-input" name="search" id="search"
                       placeholder="<?php echo $view['translator']->trans(
                           'mautic.contactclient.search.placeholder'
                       ); ?>"
                       value="<?php echo $transactions['search']; ?>">

                <div class="input-group-btn">
                    <button type="submit" id="contactClientTimelineFilterApply" name="contactClientTimelineFilterApply"
                            class="btn btn-default btn-search">
                        <i class="the-icon fa fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>
        <input type="hidden" name="contactClientId" id="contactClientId" value="<?php echo $view->escape($contactClient->getId()); ?>" />
    </form>
    <!-- Export button -->
    <div class="btn-group col-xs-2" >
        <a class="btn btn-default"
           onclick="Mautic.contactClientTimelineExport(<?php echo $contactClient->getId(); ?>);">
            <span>
                <i class="fa fa-download"></i><span class="hidden-xs hidden-sm">Export</span>
            </span>
        </a>
    </div>

    <div id="transactions-table" class="bg-whiter">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
