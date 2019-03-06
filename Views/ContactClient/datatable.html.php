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

<div class="datatable-wrapper">
    <div class="pt-sd pr-md pb-md pl-md">
        <table class="table table-striped table-bordered" width="100%" id="contactClientEventsTable">
        </table>
    </div>
</div>
<script>
    var tableData = <?php echo json_encode($tableData); ?>;
</script>
<?php
    echo $view['assets']->includeScript('plugins/MauticContactClientBundle/Assets/build/contactclient.min.js?v=2', 'contactclientOnLoad', 'contactclientOnLoad');
    echo $view['assets']->includeStylesheet('plugins/MauticContactClientBundle/Assets/build/contactclient.min.css');
?>
