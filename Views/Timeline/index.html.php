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

<div class="panel">
    <!-- filter form -->
    <form method="post" action="<?php echo $view['router']->path(
        'mautic_contactclienttimeline_action',
        ['contactClientId' => $contactClient->getId()]
    ); ?>" id="timeline-filters">
        <div class="col-xs-8 col-lg-10 va-m">
            <h4><?php echo $view['translator']->trans('mautic.contactclient.search.header'); ?></h4>
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
    </form>

<!-- Export button -->
    <div class="std-toolbar btn-group">
        <a class="btn btn-default"
           onclick="Mautic.contactClientTimelineExport(<?php echo $contactClient->getId(); ?>);">
            <i class="fa fa-download "></i>
            <span class="hidden-xs hidden-sm">Export</span>
        </a>
    </div>
</div>

<script>
    mauticLang['showMore'] = '<?php echo $view['translator']->trans('mautic.core.more.show'); ?>';
    mauticLang['hideMore'] = '<?php echo $view['translator']->trans('mautic.core.more.hide'); ?>';
</script>

<div id="timeline-table">
    <?php $view['slots']->output('_content'); ?>
</div>
