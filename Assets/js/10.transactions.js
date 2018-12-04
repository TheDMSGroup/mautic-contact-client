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