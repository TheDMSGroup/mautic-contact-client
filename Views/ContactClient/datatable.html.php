<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="datatable-wrapper">
    <div class="pt-sd pr-md pb-md pl-md">
        <table class="table table-striped table-bordered" width="100%" id="contactClientEventsTable">
        </table>
    </div>
</div>
<script>
    var tableData = <?php echo json_encode($tableData); ?>;
    mQuery.getScript(mauticBaseUrl + 'plugins/MauticContactClientBundle/Assets/build/contactclient_events_datatable.js');
</script>
