Mautic.contactclientPendingEventsTable = function () {

    if ("undefined" === mQuery.fn.dataTable) {
        mQuery.getScript(mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactLedgerBundle/Assets/js/datatables.min.js', function () {
            mQuery.getScript(mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactLedgerBundle/Assets/js/datetime-moment.js');
            mQuery.getCssOnce(mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactLedgerBundle/Assets/css/datatables.min.css');
            mQuery.getCssOnce(mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactLedgerBundle/Assets/css/dataTables.fontAwesome.css');
        });
    }
    var $tableTarget = mQuery('#pending-events-table');
    if ($tableTarget.length && !$tableTarget.hasClass('table-initialized')) {
        $tableTarget.addClass('table-initialized');
        // Make ajax call
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: 'POST',
            data: {
                action: 'plugin:contactClient:pendingEvents',
                objectId: Mautic.getEntityId(),
            },
            cache: false,
            dataType: 'json',
            success: function (response) {
                console.log(response);
                mQuery('#PendingEventsCount').text(response.total)
                    mQuery('#clientPendingEvents-builder-overlay').hide();
                    $tableTarget.append(response.html);
            } // end ajax success
        }); // end ajax call
    } // end if tableTarget exists

};

