Mautic.contactclientTimelineOnLoad = function (container, response) {

    var sortedColumn = mQuery('.contactclient-timeline a[data-sort=' + sortField + '] i');
    sortedColumn.addClass('fa-sort-amount-' + sortDirection);
    sortedColumn.removeClass('fa-sort');

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
    mQuery('.contactclient-timeline a[data-activate-details=\'all\']').on('click', function () {
        if (mQuery(this).find('span').first().hasClass('fa-level-down')) {
            mQuery('.contactclient-timeline a[data-activate-details!=\'all\']').each(function () {
                var detailsId = mQuery(this).data('activate-details'),
                    $details = mQuery('#timeline-details-' + detailsId);
                if (detailsId && $details.length) {
                    $details.removeClass('hide');
                    codeMirror($details);
                    mQuery(this).addClass('active');
                }
            });
            mQuery(this).find('span').first().removeClass('fa-level-down').addClass('fa-level-up');
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
            mQuery(this).find('span').first().removeClass('fa-level-up').addClass('fa-level-down');
        }
    });
    mQuery('.contactclient-timeline a[data-activate-details!=\'all\']').on('click', function () {
        var detailsId = mQuery(this).data('activate-details');
        console.log(detailsId);
        if (detailsId && mQuery('#timeline-details-' + detailsId).length) {
            var activateDetailsState = mQuery(this).hasClass('active'),
                $details = mQuery('#timeline-details-' + detailsId);

            if (activateDetailsState) {
                $details.addClass('hide');
                mQuery(this).find('span').first().addClass('fa-plus-square-o');
                mQuery(this).find('span').first().removeClass('fa-minus-square-o');
                mQuery(this).removeClass('active');
            }
            else {
                $details.removeClass('hide');
                codeMirror($details);
                mQuery(this).find('span').first().addClass('fa-minus-square-o');
                mQuery(this).find('span').first().removeClass('fa-plus-square-o');
                mQuery(this).addClass('active');
            }
        }
    });

    mQuery('.contactclient-timeline a.timeline-header-sort').on('click', function () {
        var column = mQuery(this).data('sort');
        var newDirection;
        if (column !== sortField) {
            newDirection = 'DESC';
        }
        else {
            newDirection = sortDirection === 'desc' ? 'ASC' : 'DESC';
        }
        mQuery('#orderBy').val(column + ':' + newDirection);
        // trigger a form submit
        mQuery('#timeline-filters').submit();

    });

    mQuery('#timeline-table:first .pagination:first a').off('click').on('click', function (e) {
        e.preventDefault();
        var urlbase = this.href.split('?')[0];
        var page = urlbase.split('/')[4];
        mQuery('#page').val(page);
        // trigger a form submit
        mQuery('#timeline-filters').submit();
    });

    if (response && typeof response.timelineCount !== 'undefined') {
        mQuery('#TimelineCount').html(response.timelineCount);
    }

    mQuery('#client-timeline-overlay').hide();
};

mQuery(function () {
    var filterForm = mQuery('#timeline-filters');
    var dateFrom = document.createElement('input');
    dateFrom.type = 'hidden';
    dateFrom.name = 'dateFrom';
    dateFrom.value = mQuery('#chartfilter_date_from').val();

    var dateTo = document.createElement('input');
    dateTo.type = 'hidden';
    dateTo.name = 'dateTo';
    dateTo.value = mQuery('#chartfilter_date_to').val();

    filterForm.append(dateFrom);
    filterForm.append(dateTo);

    filterForm.submit(function (event) {
        mQuery('#client-timeline-overlay').show(); // spinner
        event.preventDefault(); // Prevent the form from submitting via the
                                // browser
        var form = $(this);
        mQuery.ajax({
            type: form.attr('method'),
            url: mauticAjaxUrl,
            data: {
                action: 'plugin:mauticContactClient:ajaxTimeline',
                filters: form.serializeArray()
            }
        }).done(function (data) {
            mQuery('div#timeline-table').html(data);
            if (mQuery('.contactclient-timeline').length) {
                Mautic.contactclientTimelineOnLoad();
            }
        }).fail(function (data) {
            // Optionally alert the user of an error here...
            alert('Ooops! Something went wrong');
        });
    });
});

Mautic.contactClientTimelineExport = function (contactClientId) {
    var frame = document.createElement('iframe');
    var src = mauticBaseUrl + 's/contactclient/timeline/export/' + contactClientId + '?timelineFrom=' + mQuery('#chartfilter_date_from').val() + '&timelineTo=' + mQuery('#chartfilter_date_to').val();

    frame.setAttribute('src', src);
    frame.setAttribute('style', 'display: none');
    document.body.appendChild(frame);
};

Mautic.contactClientTimelineFile = function (contactClientId, fileId) {
    var frame = document.createElement('iframe');
    var src = mauticBaseUrl + 's/contactclient/timeline/file/' + contactClientId + '/' + fileId;
    frame.setAttribute('src', src);
    frame.setAttribute('style', 'display: none');
    document.body.appendChild(frame);
};