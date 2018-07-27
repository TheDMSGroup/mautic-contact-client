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
<div class="table-responsive">
    <table class="table table-hover table-bordered contactclient-transactions" style="z-index: 2; position: relative;">
        <thead>
        <tr>
            <th class="visible-md visible-lg transactions-icon">
                <a class="btn btn-sm btn-nospin btn-default" data-activate-details="all" data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.toggle_all_details'
                   ); ?>">
                    <span class="fa fa-fw fa-level-down"></span>
                </a>
            </th>
            <th class="visible-md visible-lg transactions-message">
                <a class="transactions-header-sort" data-toggle="tooltip" data-sort="message"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.message'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.message'
                    ); ?>
                    <i class="fa fa-sort"></i>
                </a>
            </th>
            <th class="visible-md visible-lg transactions-contact-id">
                <a class="transactions-header-sort" data-toggle="tooltip" data-sort="contact_id"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.contact_id'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.contact_id'
                    ); ?>
                    <i class="fa fa-sort"></i>
                </a>
            </th>
            <th class="visible-md visible-lg transactions-utm-source">
                <a class="transactions-header-sort" data-toggle="tooltip" data-sort="utm_source"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.utm_source'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.utm_source'
                    ); ?>
                    <i class="fa fa-sort"></i>
                </a>
            </th>
            <th class="visible-md visible-lg transactions.event-type">
                <a class="transactions-header-sort" data-toggle="tooltip" data-sort="type"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.event_type'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.event_type'
                    ); ?>
                    <i class="fa fa-sort"></i>
                </a>
            </th>
            <th class="visible-md visible-lg transactions-timestamp">
                <a class="transactions-header-sort" data-toggle="tooltip" data-sort="date_added"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.event_timestamp'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.event_timestamp'
                    ); ?>
                    <i class="fa fa-sort"></i>
                </a>
            </th>
        </tr>
        <tbody>
        <?php foreach ($transactions['events'] as $counter => $event): ?>
            <?php
            ++$counter; // prevent 0
            $icon       = (isset($event['icon'])) ? $event['icon'] : 'fa-history';
            $eventLabel = (isset($event['eventLabel'])) ? $event['eventLabel'] : $event['eventType'];
            $message    = (isset($event['message'])) ? $event['message'] : null;
            $contact    = (isset($event['contactId'])) ? "<a href=\"/s/contacts/view/{$event['contactId']}\" data-toggle=\"ajax\">{$event['contactId']}</a>" : null;

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
            $rowClasses = ['timeline'];
            if (0 === $counter % 2) {
                $rowClasses[] = 'timeline-row-highlighted';
            }
            if (!empty($row['fearuted'])) {
                $rowClasses[] = 'timeline-featured';
            }
            ?>
            <tr class="<?php echo implode(' ', $rowClasses); ?>">
                <td class="transactions-icon">
                    <a href="javascript:void(0);" data-activate-details="e<?php echo $counter; ?>"
                       class="btn btn-sm btn-nospin btn-default<?php if (empty($details)) {
                echo ' disabled';
            } ?>" data-toggle="tooltip" title="<?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.toggle_details'
                    ); ?>">
                        <span class="fa fa-fw <?php echo $icon; ?>"></span>
                    </a>
                </td>
                <td class="transactions-message"><?php echo $message; ?></td>
                <td class="transactions-contact-id"><?php echo $contact; ?></td>
                <td class="transactions-utm-source"><?php echo isset($event['utmSource']) ? $event['utmSource'] : ''; ?></td>
                <td class="transactions-type"><?php if (isset($event['eventType'])) {
                        echo $event['eventType'];
                    } ?></td>
                <td class="transactions-timestamp"><?php echo $view['date']->toText(
                        $event['timestamp'],
                        'local',
                        'Y-m-d H:i:s',
                        true
                    ); ?></td>
            </tr>
            <?php if (!empty($details)): ?>
                <tr class="transactions-row<?php echo $rowStripe; ?> transactions-details hide"
                    id="transactions-details-e<?php echo $counter; ?>">
                    <td colspan="5">
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
<!--/ transactions -->