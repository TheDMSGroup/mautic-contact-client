// Logic for the payload type switch.
Mautic.contactclientType = function () {

    // Trigger payload tab visibility based on contactClient type.
    mQuery('input[name="contactclient[type]"]').change(function () {
        var val = mQuery('input[name="contactclient[type]"]:checked').val();
        if (val === 'api') {
            mQuery('.row.api_payload').removeClass('hide');
            mQuery('.row.file_payload').addClass('hide');
            mQuery('#contactclient_attribution_settings').removeClass('hide');

            // Mautic.contactclientApiPayload();
            Mautic.contactclientApiPayloadPre();
        }
        else if (val === 'file') {
            mQuery('.row.api_payload').addClass('hide');
            mQuery('.row.file_payload').removeClass('hide');
            mQuery('#contactclient_attribution_settings').addClass('hide');

            Mautic.contactclientFilePayloadPre();
        }
    }).first().parent().parent().find('label.active input:first').trigger('change');

    // Hide the right column when Payload tab is open to give more room for
    // table entry.
    var activeTab = '#details';
    mQuery('.contactclient-tab').click(function () {
        var thisTab = mQuery(this).attr('href');
        if (thisTab !== activeTab) {
            activeTab = thisTab;
            if (activeTab !== '#details') {
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
};

Mautic.contactclientTypeChange = function (t) {
    setTimeout(function () {
        var $parent = mQuery(t).parent().parent();
        $parent.find('label.btn:not(.active)')
            .addClass('btn-default').removeClass('btn-success');
        $parent.find('label.btn.btn-default.active')
            .addClass('btn-success').removeClass('btn-default');
    }, 50);
    return true;
};
