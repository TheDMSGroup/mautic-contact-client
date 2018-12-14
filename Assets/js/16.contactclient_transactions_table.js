Mautic.contactclientTransactionsTable = function () {
    var $tableTarget = mQuery('#transactions-table');
    if ($tableTarget.length && !$tableTarget.hasClass('table-initialized')) {
        $tableTarget.addClass('table-initialized');
        // Make ajax call
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: 'POST',
            data: {
                action: 'plugin:mauticContactClient:transactions',
                objectId: window.location.pathname.split('/').pop()
            },
            cache: true,
            dataType: 'json',
            success: function (response) {
                if(response.success>0){
                    mQuery('#clientTransactions-builder-overlay').hide();
                    $tableTarget.append(response.html);
                    Mautic.contactclientTransactionsOnLoad();
                }
            } // end ajax success
        }); // end ajax call
    } // end if tableTarget exists
};
