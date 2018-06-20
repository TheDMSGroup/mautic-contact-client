Mautic.loadContactClientEventsDatatable = function (tableData) {
    var $sourcetarget = mQuery('#contactClientEventsTable');
    if ($sourcetarget.length) {
        mQuery('#contactClientEventsTable:first:not(.table-initialized)').addClass('table-initialized').each(function () {
            mQuery.getScriptCachedOnce(mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactLedgerBundle/Assets/js/datatables.min.js', function () {
                mQuery.getCssOnce(mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactLedgerBundle/Assets/css/datatables.min.css', function () {
                    mQuery.getCssOnce(mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactLedgerBundle/Assets/css/dataTables.fontAwesome.css', function () {
                        // dependent files loaded, now get the data and render
                        mQuery('#contactClientEventsTable').DataTable({
                            language: {
                                emptyTable: 'No results found for this date range and filters.'
                            },
                            data: tableData.data,
                            autoFill: true,
                            columns: tableData.labels,
                            bSort : false,
                            order: [0, 'asc'],
                            bLengthChange: true,
                            columnDefs: [
                                {
                                    "targets": [ 0 ],
                                    "visible": false,
                                    "searchable": false
                                }
                                ]
                        });
                    }); //getScriptsCachedOnce - fonteawesome css
                });//getScriptsCachedOnce - datatables css
            });  //getScriptsCachedOnce - datatables js
        });
    }
};

mQuery(document).ready(function () {
    if (!mQuery('#contactClientEventsTable').hasClass('table-done')) {
        Mautic.loadContactClientEventsDatatable(tableData);
    }
});

// getScriptCachedOnce for faster page loads in the backend.
mQuery.getScriptCachedOnce = function (url, callback) {
    if (
        typeof window.getScriptCachedOnce !== 'undefined'
        && window.getScriptCachedOnce.indexOf(url) !== -1
    ) {
        callback();
        return mQuery(this);
    }
    else {
        return mQuery.ajax({
            url: url,
            dataType: 'script',
            cache: true
        }).done(function () {
            if (typeof window.getScriptCachedOnce === 'undefined') {
                window.getScriptCachedOnce = [];
            }
            window.getScriptCachedOnce.push('url');
            callback();
        });
    }
};

// getScriptCachedOnce for faster page loads in the backend.
mQuery.getCssOnce = function (url, callback) {
    if (document.createStyleSheet) {
        document.createStyleSheet(url);
    }
    else {
        mQuery('head').append(mQuery('<link rel=\'stylesheet\' href=\'' + url + '\' type=\'text/css\' />'));
    }
    callback();
};

