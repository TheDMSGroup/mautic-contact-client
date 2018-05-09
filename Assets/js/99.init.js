// General helpers for the Contact Client editor form.
Mautic.contactclientOnLoad = function () {
    mQuery(document).ready(function () {

        // Default behavior for Contact Client edit/details screens:
        if (mQuery('input[name="contactclient[type]"]').length) {
            Mautic.contactclientType();
            Mautic.contactclientDuplicate();
            Mautic.contactclientExclusive();
            Mautic.contactclientFilter();
            Mautic.contactclientLimits();
            Mautic.contactclientSchedule();
            Mautic.contactclientAttribution();
            return;
        }

        if (mQuery('.contactclient-timeline').length) {
            Mautic.contactclientTimelineOnLoad();
        }
    });
};