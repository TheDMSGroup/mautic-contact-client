<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (isset($tmpl) && 'index' == $tmpl) {
    $view->extend('MauticContactClientBundle:Timeline:index.html.php');
}

$order   = $files['order'];
$baseUrl = $view['router']->path(
    'mautic_contactclient_timeline_action',
    [
        'contactClientId' => $contactClient->getId(),
    ]
);
?>

<!-- timeline -->
<div class="table-responsive">
    <table class="table table-hover table-bordered" id="contactclient-timeline" style="z-index: 2; position: relative;">
        <thead>
        <tr>
            <th class="visible-md visible-lg timeline-icon">
                <a class="btn btn-sm btn-nospin btn-default" data-activate-details="all" data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.timeline.toggle_all_details'
                   ); ?>">
                    <span class="fa fa-fw fa-level-down"></span>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-filename">
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="filename"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.timeline.filename'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.timeline.filename'
                    ); ?>
                    <i class="fa fa-sort"></i>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-type">
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="type"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.timeline.type'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.timeline.type'
                    ); ?>
                    <i class="fa fa-sort"></i>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-status">
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="status"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.timeline.status'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.timeline.status'
                    ); ?>
                    <i class="fa fa-sort"></i>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-crc32">
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="crc32"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.timeline.crc32'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.timeline.crc32'
                    ); ?>
                    <i class="fa fa-sort"></i>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-timestamp">
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="timestamp"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.timeline.event_timestamp'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.timeline.event_timestamp'
                    ); ?>
                    <i class="fa fa-sort"></i>
                </a>
            </th>
        </tr>
        <tbody>
        <?php foreach ($files['files'] as $counter => $file): ?>
            <?php
            /** @var \MauticPlugin\MauticContactClientBundle\Entity\File $file */
            $name   = $file->getName();
            $type   = $file->getType();
            $status = ucwords($file->getStatus());
            $crc32  = $file->getCrc32();
            $logs   = $file->getLogs();
            $icon   = 'fa-plus-square-o';
            ++$counter;
            $rowStripe = (0 === $counter % 2) ? ' timeline-row-highlighted' : '';
            ?>
            <tr class="timeline-row<?php echo $rowStripe; ?><?php if (\MauticPlugin\MauticContactClientBundle\Entity\File::STATUS_READY === $status) {
                echo ' timeline-featured';
            } ?>">
                <td class="timeline-icon">
                    <a href="javascript:void(0);" data-activate-details="<?php echo $counter; ?>"
                       class="btn btn-sm btn-nospin btn-default<?php if (empty($logs)) {
                           echo ' disabled';
                       } ?>" data-toggle="tooltip" title="<?php echo $view['translator']->trans(
                        'mautic.contactclient.timeline.toggle_details'
                    ); ?>">
                        <span class="fa fa-fw <?php echo $icon; ?>"></span>
                    </a>
                </td>
                <td class="timeline-name"><?php echo $name; ?></td>
                <td class="timeline-name"><?php echo $type; ?></td>
                <td class="timeline-name"><?php echo $status; ?></td>
                <td class="timeline-name"><?php echo $crc32; ?></td>
                <td class="timeline-timestamp"><?php echo $view['date']->toText(
                        $file->getDateModified(),
                        'local',
                        'Y-m-d H:i:s',
                        true
                    ); ?></td>
            </tr>
            <?php if (!empty($logs)): ?>
                <tr class="timeline-row<?php echo $rowStripe; ?> timeline-details hide"
                    id="timeline-details-<?php echo $counter; ?>">
                    <td colspan="5">
                        <?php echo $logs; ?>
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
        'baseUrl'    => '/page',
        'target'     => '',
        'totalItems' => $files['total'],
    ]
); ?>
<script>
    // put correct sort icons on timeline table headers
    var sortField = '<?php echo $order[0]; ?>';
    var sortDirection = '<?php echo strtolower($order[1]); ?>';
</script>
<!--/ timeline -->