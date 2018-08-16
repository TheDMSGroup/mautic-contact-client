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
    $view->extend('MauticContactClientBundle:Transactions:index.html.php');
}

$baseUrl = $view['router']->path(
    'mautic_contactclient_transactions',
    [
        'objectId' => $contactClient->getId(),
    ]
);
?>

<!-- transactions -->
<script>
    // put correct sort icons on timeline table headers
    var sortField = '<?php echo $order[0]; ?>';
    var sortDirection = '<?php echo strtolower($order[1]); ?>';
</script>
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
            <th class="visible-md visible-lg timeline-message">
                <a data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.message'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.message'
                    ); ?>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-contact-id">
                <a data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.contact_id'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.contact_id'
                    ); ?>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-utm-source">
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="utm_source"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.utm_source'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.utm_source'
                    ); ?>
                    <i class="fa fa-sort<?php echo 'utm_source' === $order[0] ? '-amount-'.strtolower($order[1]) : ''; ?>"></i>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-event-type">
                <a data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.event_type'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.event_type'
                    ); ?>
                </a>
            </th>
            <th class="visible-md visible-lg timeline-timestamp">
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="date_added"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.event_timestamp'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.event_timestamp'
                    ); ?>
                    <i class="fa fa-sort<?php echo 'date_added' === $order[0] ? '-amount-'.strtolower($order[1]) : ''; ?>"></i>
                </a>
            </th>
        </tr>
        <tbody>
        <?php
        $counter = 0;
        /** @var \MauticPlugin\MauticContactClientBundle\Entity\Event $event */
        foreach ($transactions['events'] as $event):
            ++$counter; // prevent 0
            $icon        = (isset($event['icon'])) ? $event['icon'] : 'fa-history';
            $eventLabel  = (isset($event['eventLabel'])) ? $event['eventLabel'] : $event['eventType'];
            $message     = (isset($event['message'])) ? $event['message'] : null;
            $contactPath = $view['router']->path(
                'mautic_contact_action',
                [
                    'objectAction' => 'view',
                    'objectId'     => $event['contactId'],
                ]
            );
            $contact     = (isset($event['contactId'])) ? "<a href=\"{$contactPath}\" data-toggle=\"ajax\">{$event['contactId']}</a>" : null;

            if (is_array($eventLabel)):
                $linkType   = empty($eventLabel['isExternal']) ? 'data-toggle="ajax"' : 'target="_new"';
                $eventLabel = isset($eventLabel['href']) ? "<a href=\"{$eventLabel['href']}\" $linkType>{$eventLabel['label']}</a>" : "{$eventLabel['label']}";
            endif;

            $details = '';
            if (isset($event['contentTemplate']) && $view->exists($event['contentTemplate'])):
                $details = trim(
                    $view->render($event['contentTemplate'], ['event' => $event, 'contactClient' => $contactClient])
                );
            endif;
            $rowClasses = [];
            if (0 === $counter % 2) {
                $rowClasses[] = 'timeline-row-highlighted';
            }
            if (!empty($row['featured'])) {
                $rowClasses[] = 'timeline-featured';
            }
            ?>
            <tr class="<?php echo implode(' ', $rowClasses); ?>">
                <td class="timeline-icon">
                    <a href="javascript:void(0);" data-activate-details="e<?php echo $counter; ?>"
                       class="btn btn-sm btn-nospin btn-default<?php if (empty($details)) {
                echo ' disabled';
            } ?>" data-toggle="tooltip" title="<?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.toggle_details'
                    ); ?>">
                        <span class="fa fa-fw <?php echo $icon; ?>"></span>
                    </a>
                </td>
                <td class="timeline-message"><?php echo $message; ?></td>
                <td class="timeline-contact-id"><?php echo $contact; ?></td>
                <td class="timeline-utm-source"><?php echo isset($event['utmSource']) ? $event['utmSource'] : ''; ?></td>
                <td class="timeline-type"><?php echo isset($event['eventType']) ? $event['eventType'] : ''; ?></td>
                <td class="timeline-timestamp"><?php echo $view['date']->toText(
                        $event['timestamp'],
                        'local',
                        'Y-m-d H:i:s',
                        true
                    ); ?></td>
            </tr>
            <?php if (!empty($details)): ?>
            <tr class="<?php echo implode(' ', $rowClasses); ?> timeline-details hide"
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
        'page'       => $transactions['page'],
        'fixedPages' => $transactions['maxPages'],
        'fixedLimit' => true,
        'baseUrl'    => $baseUrl,
        'target'     => '#transactions-table',
        'totalItems' => $transactions['total'],
    ]
); ?>