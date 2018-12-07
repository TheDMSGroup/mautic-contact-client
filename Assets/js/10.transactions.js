Mautic.contactclientTransactionsOnLoad = function (container, response) {
    // Function for activating Codemirror in transactions
    var codeMirror = function ($el) {
        if (!$el.hasClass('codemirror-active')) {
            var $textarea = $el.find('textarea.codeMirror-json');
            if ($textarea.length) {
                CodeMirror.fromTextArea($textarea[0], {
                    mode: {
                        name: 'javascript',
                        json: true
                    },
                    theme: 'cc',
                    gutters: [],
                    lineNumbers: false,
                    lineWrapping: true,
                    readOnly: true
                });
            }
            $el.addClass('codemirror-active');
        }
    };

    mQuery('.contactclient-timeline a[data-activate-details=\'all\']').off('click').on('click', function () {
        if (mQuery(this).find('span:first').hasClass('fa-level-down')) {
            mQuery('.contactclient-timeline a[data-activate-details!=\'all\']').each(function () {
                var detailsId = mQuery(this).data('activate-details'),
                    $details = mQuery('#timeline-details-' + detailsId);
                if (detailsId && $details.length) {
                    $details.removeClass('hide');
                    codeMirror($details);
                    mQuery(this).addClass('active');
                }
            });
            mQuery(this).find('span:first')
                .removeClass('fa-level-down')
                .addClass('fa-level-up');
        }
        else {
            mQuery('.contactclient-timeline a[data-activate-details!=\'all\']').each(function () {
                var detailsId = mQuery(this).data('activate-details'),
                    $details = mQuery('#timeline-details-' + detailsId);
                if (detailsId && $details.length) {
                    $details.addClass('hide');
                    mQuery(this).removeClass('active');
                }
            });
            mQuery(this).find('span:first')
                .removeClass('fa-level-up')
                .addClass('fa-level-down');
        }
    });
    // Expand/collapse single transactions.
    mQuery('.contactclient-timeline .timeline-icon  a[data-activate-details!=\'all\']').off('click').on('click', function () {
        var detailsId = mQuery(this).data('activate-details');
        if (detailsId && mQuery('#timeline-details-' + detailsId).length) {
            var activateDetailsState = mQuery(this).hasClass('active'),
                $details = mQuery('#timeline-details-' + detailsId);

            if (activateDetailsState) {
                $details.addClass('hide');
                mQuery(this)
                    .removeClass('active')
                    .find('span:first')
                    .addClass('fa-plus-square-o')
                    .removeClass('fa-minus-square-o');
            }
            else {
                $details.removeClass('hide');
                codeMirror($details);
                mQuery(this).addClass('active')
                    .find('span:first')
                    .addClass('fa-minus-square-o')
                    .removeClass('fa-plus-square-o');
            }
        }
    });

    // add Transaction Totals to the tab
    mQuery('span#TransactionsCount').html(contactClient.transactionsTotal);
    mQuery('#transactions-filter-btn').unbind('click').click(function () {
        mQuery('.transaction-filter').toggle();
    });

    // Register Form Submission control events
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
        Mautic.contactClientTransactionFormSubmit(this);

    });
};

Mautic.contactClientTimelineExport = function (contactClientId) {
    // grab timeline filter values to send for export params
    var messageVar = mQuery('#filter-message').val();
    var typeVar = mQuery('#filter-type').val();
    var utm_sourceVar = mQuery('#filter-utm_source').val();
    var contact_idVar = mQuery('#filter-contact_id').val();
    var params = jQuery.param({
        message: messageVar,
        type: typeVar,
        utm_source: utm_sourceVar,
        contact_id: contact_idVar
    });

    var frame = document.createElement('iframe');
    var src = mauticBaseUrl + 's/contactclient/view/' + contactClientId + '/transactions/export?' + params;
    frame.setAttribute('src', src);
    frame.setAttribute('style', 'display: none');
    document.body.appendChild(frame);
};

Mautic.contactClientTimelineFile = function (contactClientId, fileId) {
    var frame = document.createElement('iframe');
    var src = mauticBaseUrl + 's/contactclient/view/' + contactClientId + '/files/file/' + fileId;
    frame.setAttribute('src', src);
    frame.setAttribute('style', 'display: none');
    document.body.appendChild(frame);
};

Mautic.contactClientTransactionFormSubmit = function(form){
    //merge the chartfilter form to the transaction filter before re-submiting it
    mQuery('#transactions_dateFrom').val(mQuery('#chartfilter_date_from').val());
    mQuery('#transactions_dateTo').val(mQuery('#chartfilter_date_to').val());
    mQuery('#transactions_campaignId').val(mQuery('#chartfilter_campaign').val());

    //merge the filter fields to the transaction filter before re-submiting it
    mQuery('#transaction_message').val(mQuery('#filter-message').val());
    mQuery('#transaction_contact_id').val(mQuery('#filter-contact_id').val());
    mQuery('#transaction_utmsource').val(mQuery('#filter-utm_source').val());
    mQuery('#transaction_type').val(mQuery('#filter-type').val());

    var form = $(form);
    mQuery.ajax({
        type: form.attr('method'),
        url: mauticAjaxUrl,
        data: {
            action: 'plugin:mauticContactClient:transactions',
            filters: form.serializeArray(),
            objectId: contactClient.id
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
}