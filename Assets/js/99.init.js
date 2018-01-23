// General helpers for the Contact Client editor form.
Mautic.contactclientOnLoad = function () {
    mQuery(document).ready(function () {

        // Default behavior for Contact Client edit/details screens:
        var $type = mQuery('input[name="contactclient[type]"]:first');
        if ($type.length === 1) {
            // Trigger payload tab visibility based on contactClient type.
            mQuery('input[name="contactclient[type]"]').change(function () {
                var val = mQuery(this).val();
                if (val === 'api') {
                    mQuery('#payload-tab, #contactclient_api_payload, #api_payload_advanced, #contactclient_api_payload_codemirror, #contactclient_jsoneditor').removeClass('hide');
                    mQuery('#contactclient_file_payload').addClass('hide');
                    Mautic.contactclientApiPayload();
                }
                else if (val === 'file') {
                    mQuery('#contactclient_api_payload, #api_payload_advanced, #contactclient_api_payload_codemirror, #contactclient_jsoneditor').addClass('hide');
                    mQuery('#payload-tab, #contactclient_file_payload').removeClass('hide');
                    Mautic.contactclientFilePayload();
                }
                else {
                    mQuery('#contactclient_api_payload, #contactclient_file_payload, #payload-tab').addClass('hide');
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

            Mautic.contactclientExclusivity();
            Mautic.contactclientFilter();
            Mautic.contactclientLimits();
            Mautic.contactclientSchedule();
            return;
        }
    });
};
