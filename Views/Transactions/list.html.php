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
        'objectId'     => $contactClient->getId(),
        'objectAction' => 'view',
    ]
);

$toggleDir = 'DESC' == $order[1] ? 'ASC' : 'DESC';
//display filters if we have filter values
$filterDisplay = 'style="display:none;"';
if (
    (isset($transactions['filters']['message']) && !empty($transactions['filters']['message']))
    || (isset($transactions['filters']['type']) && !empty($transactions['filters']['type']))
    || (isset($transactions['filters']['utm_source']) && !empty($transactions['filters']['utm_source']))
    || (isset($transactions['filters']['contact_id']) && !empty($transactions['filters']['contact_id']))
) {
    $filterDisplay = ''; // visible
}
?>

<!-- filter form -->
<form method="post" id="transactions-filters"
      data-toggle="ajax"
      data-target="#transactions-table"
      data-overlay="true"
      data-overlay-target="#clientTransactions-builder-overlay"
      data-action="<?php echo $view['router']->path(
          'mautic_contactclient_transactions',
          ['objectId' => $contactClient->getId(), 'objectAction' => 'view']
      ); ?>">
    <input type="hidden" name="message" id="transaction_message"
           value="<?php echo isset($transactions['filters']['message']) ? $transactions['filters']['message'] : null; ?>">
    <input type="hidden" name="utm_source" id="transaction_utmsource"
           value="<?php echo isset($transactions['filters']['utmsource']) ? $transactions['filters']['utmsource'] : null; ?>">
    <input type="hidden" name="contact_id" id="transaction_contact_id"
           value="<?php echo isset($transactions['filters']['contact_id']) ? $transactions['filters']['contact_id'] : null; ?>">
    <input type="hidden" name="type" id="transaction_type"
           value="<?php echo isset($transactions['filters']['type']) ? $transactions['filters']['type'] : null; ?>">
    <input type="hidden" name="objectId" id="objectId" value="<?php echo $view->escape($contactClient->getId()); ?>"/>
    <input type="hidden" name="orderby" id="orderby" value="<?php echo $transactions['order'][0]; ?>">
    <input type="hidden" name="orderbydir" id="orderbydir" value="<?php echo $transactions['order'][1]; ?>">
    <input type="hidden" name="dateFrom" id="transactions_dateFrom"
           value="<?php echo isset($transactions['dateFrom']) ? $transactions['dateFrom'] : null; ?>">
    <input type="hidden" name="dateTo" id="transactions_dateTo"
           value="<?php echo isset($transactions['dateTo']) ? $transactions['dateTo'] : null; ?>">
    <input type="hidden" name="campaignId" id="transactions_campaignId"
           value="<?php echo isset($transactions['filters']['campaignId']) ? $transactions['filters']['campaignId'] : null; ?>">
    <input type="hidden" name="page" id="transactions_page" value="<?php echo $transactions['page']; ?>">
</form>


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
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="message"
                   data-sort_dir="<?php echo 'message' === $order[0] ? $toggleDir : 'DESC'; ?>"
                   data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.message'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.message'
                    ); ?>
                    <i class="fa fa-sort<?php echo 'message' === $order[0] ? '-amount-'.strtolower(
                            $order[1]
                        ) : ''; ?>"></i>
                </a>
                <input class="transaction-filter" id="filter-message"
                       name="filter-message" <?php echo $filterDisplay; ?>
                       size="20"
                       placeholder="Message contains..."
                       value="<?php echo isset($transactions['filters']['message']) ? $transactions['filters']['message'] : null; ?>">
            </th>
            <th class="visible-md visible-lg timeline-contact-id">
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="contact_id"
                   data-sort_dir="<?php echo 'contact_id' === $order[0] ? $toggleDir : 'DESC'; ?>"
                   data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.contact_id'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.contact_id'
                    ); ?>
                    <i class="fa fa-sort<?php echo 'contact_id' === $order[0] ? '-amount-'.strtolower(
                            $order[1]
                        ) : ''; ?>"></i>
                </a>
                <input class="transaction-filter" id="filter-contact_id"
                       name="filter-contact_id" <?php echo $filterDisplay; ?>
                       size="10"
                       placeholder="Contact ID ="
                       value="<?php echo isset($transactions['filters']['contact_id']) ? $transactions['filters']['contact_id'] : null; ?>">
            </th>
            <th class="visible-md visible-lg timeline-utm-source">
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="utm_source"
                   data-sort_dir="<?php echo 'utm_source' === $order[0] ? $toggleDir : 'DESC'; ?>"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.utm_source'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.utm_source'
                    ); ?>
                    <i class="fa fa-sort<?php echo 'utm_source' === $order[0] ? '-amount-'.strtolower(
                            $order[1]
                        ) : ''; ?>"></i>
                </a>
                <input class="transaction-filter" id="filter-utm_source"
                       name="filter-utm_source" <?php echo $filterDisplay; ?>
                       size="10"
                       placeholder="UTM Source ="
                       value="<?php echo isset($transactions['filters']['utm_source']) ? $transactions['filters']['utm_source'] : null; ?>">
            </th>
            <th class="visible-md visible-lg timeline-event-type">
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="type"
                   data-sort_dir="<?php echo 'type' === $order[0] ? $toggleDir : 'DESC'; ?>"
                   data-toggle="tooltip"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.event_type'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.event_type'
                    ); ?>
                    <i class="fa fa-sort<?php echo 'type' === $order[0] ? '-amount-'.strtolower(
                            $order[1]
                        ) : ''; ?>"></i>
                </a><input class="transaction-filter" id="filter-type" name="filter-type" <?php echo $filterDisplay; ?>
                           size="10"
                           placeholder="Type ="
                           value="<?php echo isset($transactions['filters']['type']) ? $transactions['filters']['type'] : null; ?>">
            </th>
            <th class="visible-md visible-lg timeline-timestamp">
                <a class="timeline-header-sort" data-toggle="tooltip" data-sort="date_added"
                   data-sort_dir="<?php echo 'date_added' === $order[0] ? $toggleDir : 'DESC'; ?>"
                   title="<?php echo $view['translator']->trans(
                       'mautic.contactclient.transactions.event_timestamp'
                   ); ?>">
                    <?php echo $view['translator']->trans(
                        'mautic.contactclient.transactions.event_timestamp'
                    ); ?>
                    <i class="fa fa-sort<?php echo 'date_added' === $order[0] ? '-amount-'.strtolower(
                            $order[1]
                        ) : ''; ?>"></i>
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
<script>
    mQuery('span#TransactionsCount').html(<?php echo $transactions['total']; ?>);
    mQuery('#transactions-filter-btn').unbind('click').click(function () {
        mQuery('.transaction-filter').toggle();
    });
</script>

<script>
    // Form Submission controls
    mQuery('#transactions-table .pagination-wrapper .pagination a').not('.disabled a').click(function (event) {
        event.preventDefault();
        var arg = this.href.split('?')[1];
        var page = arg.split('/')[1];
        var filterForm = mQuery('#transactions-filters');
        mQuery('#transactions_page').val(page);
        Mautic.startPageLoadingBar();
        filterForm.submit();
    });

    mQuery('.timeline-header-sort').click(function (event) {
        console.log('sorting...');
        var filterForm = mQuery('#transactions-filters');
        mQuery('#orderby').val(mQuery(this).data('sort'));
        mQuery('#orderbydir').val(mQuery(this).data('sort_dir'));
        Mautic.startPageLoadingBar();
        filterForm.submit();
    });

    mQuery('.transaction-filter').change(function (event) {
        var filterForm = mQuery('#transactions-filters');
        mQuery('#transactions_page').val(1); // reset page to 1 when filtering
        Mautic.startPageLoadingBar();
        filterForm.submit();
    });

    mQuery('#transactions-filters').submit(function (event) {
        event.preventDefault(); // Prevent the form from submitting via the browser

        //merge the chartfilter form to the transaction filter before re-submiting it
        mQuery('#transactions_dateFrom').val(mQuery('#chartfilter_date_from').val());
        mQuery('#transactions_dateTo').val(mQuery('#chartfilter_date_to').val());
        mQuery('#transactions_campaignId').val(mQuery('#chartfilter_campaign').val());

        //merge the filter fields to the transaction filter before re-submiting it
        mQuery('#transaction_message').val(mQuery('#filter-message').val());
        mQuery('#transaction_contact_id').val(mQuery('#filter-contact_id').val());
        mQuery('#transaction_utmsource').val(mQuery('#filter-utm_source').val());
        mQuery('#transaction_type').val(mQuery('#filter-type').val());

        var form = $(this);
        mQuery.ajax({
            type: form.attr('method'),
            url: mauticAjaxUrl,
            data: {
                action: 'plugin:mauticContactClient:transactions',
                filters: form.serializeArray(),
                objectId: <?php echo $view->escape($contactClient->getId()); ?>
            }
        }).done(function (data) {
            mQuery('div#transactions-table').html(data.html);
            mQuery('span#TransactionsCount').html(data.total);
            Mautic.contactclientTransactionsOnLoad();
            Mautic.stopPageLoadingBar();

        }).fail(function (data) {
            // Optionally alert the user of an error here...
            alert('Ooops! Something went wrong');
        });
    });


</script>
