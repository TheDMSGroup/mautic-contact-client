<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (isset($tmpl) && 'index' == $tmpl) {
    $view->extend('MauticContactClientBundle:Files:index.html.php');
}

$baseUrl = $view['router']->path(
    'mautic_contactclient_files',
    [
        'objectId' => $contactClient->getId(),
    ]
);

?>
<script>
    // put correct sort icons on timeline table headers
    var sortField = '<?php echo $order[0]; ?>';
    var sortDirection = '<?php echo strtolower($order[1]); ?>';
</script>

<!-- timeline -->
<div class="table-responsive">
    <table class="table table-hover table-bordered contactclient-timeline" style="z-index: 2; position: relative;">
        <thead>
        <tr>
            <th class="visible-md visible-lg timeline-icon">
                <a class="btn btn-sm btn-nospin btn-default" data-activate-details="all" data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.toggle_all_details'
                   ); ?>">
                    <span class="fa fa-fw fa-level-down"></span>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-name">
                <a data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.files.name'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.files.name'
                    ); ?>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-type">
                <a data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.files.type'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.files.type'
                    ); ?>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-status">
                <a data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.files.status'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.files.status'
                    ); ?>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-count">
                <a data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.files.count'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.files.count'
                    ); ?>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-crc32">
                <a data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.files.crc32'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.files.crc32'
                    ); ?>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-timestamp">
                <a data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.files.event_timestamp'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.files.event_timestamp'
                    ); ?>
                </a>
            </th>
        </tr>
        <tbody>
        <?php foreach ($files['files'] as $counter => $file): ?>
            <?php
            /** @var \MauticPlugin\MauticContactClientBundle\Entity\File $file */
            $id       = $file->getId();
            $location = $file->getLocation();
            $name     = $file->getName();
            $type     = $file->getType();
            $status   = ucwords($file->getStatus());
            $count    = $file->getCount();
            $crc32    = $file->getCrc32();
            $details  = $file->getLogs();
            $icon     = 'fa-plus-square-o';
            ++$counter;
            $rowStripe = (0 === $counter % 2) ? ' timeline-row-highlighted' : '';
            ?>
            <tr class="timeline-row<?php echo $rowStripe; ?><?php if (\MauticPlugin\MauticContactClientBundle\Entity\File::STATUS_READY === $status) {
                echo ' timeline-featured';
            } ?>">
                <td class="timeline-icon">
                    <a href="javascript:void(0);" data-activate-details="f<?php echo $counter; ?>"
                       class="btn btn-sm btn-nospin btn-default<?php if (empty($details)) {
                echo ' disabled';
            } ?>" data-toggle="tooltip" title="<?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.toggle_details'
                    ); ?>">
                        <span class="fa fa-fw <?php echo $icon; ?>"></span>
                    </a>
                </td>
                <td class="timeline-name">
                    <?php if ($location): ?>
                        <a class="btn btn-default"
                           onclick="Mautic.contactClientTimelineFile(<?php echo $contactClient->getId(); ?>, <?php echo $id; ?>);">
                            <span>
                                <i class="fa fa-download"></i>
                                <span class="hidden-xs hidden-sm">
                                    <?php echo $name; ?>
                                </span>
                            </span>
                        </a>
                    <?php else: ?>
                        <?php echo $name; ?>
                    <?php endif; ?>
                </td>
                <td class="timeline-type"><?php echo $type; ?></td>
                <td class="timeline-status"><?php echo $status; ?></td>
                <td class="timeline-count"><?php echo $count; ?></td>
                <td class="timeline-crc32"><?php echo $crc32; ?></td>
                <td class="timeline-timestamp"><?php echo $view['date']->toText(
                        $file->getDateModified(),
                        'local',
                        'Y-m-d H:i:s',
                        true
                    ); ?></td>
            </tr>
            <?php if (!empty($details)): ?>
                <tr class="timeline-details hide"
                    id="timeline-details-e<?php echo $counter; ?>">
                    <td colspan="6">
                        <?php echo $details; ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
echo $view->render(
    'MauticCoreBundle:Helper:pagination.html.php',
    [
        'page'       => $files['page'],
        'fixedPages' => $files['maxPages'],
        'fixedLimit' => true,
        'baseUrl'    => $baseUrl,
        'target'     => '#files-table',
        'totalItems' => $files['total'],
    ]
); ?>
<!--/ timeline -->