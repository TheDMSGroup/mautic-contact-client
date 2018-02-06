// Logic for the payload type switch.
Mautic.contactclientType = function () {

    if (typeof window.contactclientTypeLoaded === 'undefined') {
        window.contactclientTypeLoaded = true;
        // Trigger payload tab visibility based on contactClient type.
        mQuery('input[name="contactclient[type]"]').change(function () {
            var val = mQuery('input[name="contactclient[type]"]:checked').val();
            if (val === 'api') {
                mQuery('#payload-tab').removeClass('hide');
                mQuery('.row.api_payload').removeClass('hide');
                mQuery('.row.file_payload').addClass('hide');

                Mautic.contactclientApiPayload();
            }
            else if (val === 'file') {
                mQuery('#payload-tab').removeClass('hide');
                mQuery('.row.api_payload').addClass('hide');
                mQuery('.row.file_payload').removeClass('hide');

                Mautic.contactclientFilePayload();
            }
            else {
                mQuery('#payload-tab').addClass('hide');
            }
        }).first().parent().parent().find('label.active input:first').trigger('change');

        // Hide the right column when Payload tab is open to give more room for
        // table entry.
        var activeTab = '#details';
        mQuery('.contactclient-tab').click(function () {
            var thisTab = mQuery(this).attr('href');
            if (thisTab !== activeTab) {
                activeTab = thisTab;
                if (activeTab === '#payload') {
                    // Expanded view.
                    mQuery('.contactclient-left').addClass('col-md-12').removeClass('col-md-9');
                    mQuery('.contactclient-right').addClass('hide');
                }
                else {
                    // Standard view.
                    mQuery('.contactclient-left').removeClass('col-md-12').addClass('col-md-9');
                    mQuery('.contactclient-right').removeClass('hide');
                }
            }
        });
    }
};

Mautic.contactclientTypeChange = function (t) {
    setTimeout(function () {
        var $parent = mQuery(t).parent().parent();
        $parent.find('label.btn:not(.active)')
            .addClass('btn-default').removeClass('btn-primary');
        $parent.find('label.btn.btn-default.active')
            .addClass('btn-primary').removeClass('btn-default');
    }, 50);
    return true;
};
