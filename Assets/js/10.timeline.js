Mautic.contactclientTimelineOnLoad = function (container, response) {
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
    mQuery('.contactclient-timeline a[data-activate-details!=\'all\']').off('click').on('click', function () {
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
    // Change sorting.
    var $filterForm = mQuery('form#transactions-filters:first');
    mQuery('.contactclient-timeline a.timeline-header-sort').off('click').on('click', function (event) {
        event.preventDefault();
        $filterForm.find('#orderby').val(mQuery(this).data('sort'));
        $filterForm.find('#orderbydir').val(mQuery(this).find('.fa:first').hasClass('fa-sort-amount-asc') ? 'DESC' : 'ASC');
        $filterForm.submit();
    });

    // Ajaxify the form.
    $filterForm.unbind('submit').submit(function (event) {
        var $form = mQuery(this);
        event.preventDefault();
        $form.find('input').attr('disabled', 'disabled');
        Mautic.startPageLoadingBar();
        mQuery('#client-timeline-overlay').show();
        event.preventDefault();
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: 'POST',
            data: {
                action: 'plugin:mauticContactClient:transactions',
                objectId: $form.find('#objectId').val(),
                search: $form.find('#search').val(),
                orderby: $form.find('#orderby').val(),
                orderbydir: $form.find('#orderbydir').val()
            },
            cache: false,
            dataType: 'json'
        }).done(function (data) {
            mQuery('#transactions-table').html(data.html);
            $form.find('input').attr('disabled', false);
            $form.find('#contactClientTimelineFilterApply i')
                .removeClass('fa-spin')
                .removeClass('fa-spinner')
                .addClass('search');
            Mautic.stopPageLoadingBar();
            setTimeout(function () {
                Mautic.contactclientTimelineOnLoad();
            }, 500);
        }).fail(function (data) {
            console.error('Something went wrong with the transaction form ajax.');
        });
    });
};

Mautic.contactClientTimelineExport = function (contactClientId) {
    var frame = document.createElement('iframe');
    var src = mauticBaseUrl + 's/contactclient/view/' + contactClientId + '/transactions/export';
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