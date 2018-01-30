Mautic.contactclientTimelineOnLoad = function (container, response) {
    mQuery("#contactclient-timeline a[data-activate-details='all']").on('click', function() {
        if (mQuery(this).find('span').first().hasClass('fa-level-down')) {
            mQuery("#contactclient-timeline a[data-activate-details!='all']").each(function () {
                var detailsId = mQuery(this).data('activate-details');
                if (detailsId && mQuery('#timeline-details-'+detailsId).length) {
                    mQuery('#timeline-details-' + detailsId).removeClass('hide');
                    mQuery(this).addClass('active');
                }
            });
            mQuery(this).find('span').first().removeClass('fa-level-down').addClass('fa-level-up');
        } else {
            mQuery("#contactclient-timeline a[data-activate-details!='all']").each(function () {
                var detailsId = mQuery(this).data('activate-details');
                if (detailsId && mQuery('#timeline-details-'+detailsId).length) {
                    mQuery('#timeline-details-' + detailsId).addClass('hide');
                    mQuery(this).removeClass('active');
                }
            });
            mQuery(this).find('span').first().removeClass('fa-level-up').addClass('fa-level-down');
        }
    });
    mQuery("#contactclient-timeline a[data-activate-details!='all']").on('click', function() {
        var detailsId = mQuery(this).data('activate-details');
        if (detailsId && mQuery('#timeline-details-'+detailsId).length) {
            var activateDetailsState = mQuery(this).hasClass('active');

            if (activateDetailsState) {
                mQuery('#timeline-details-'+detailsId).addClass('hide');
                mQuery(this).removeClass('active');
            } else {
                mQuery('#timeline-details-'+detailsId).removeClass('hide');
                mQuery(this).addClass('active');
            }
        }
    });

    if (response && typeof response.timelineCount != 'undefined') {
        mQuery('#TimelineCount').html(response.timelineCount);
    }
};
