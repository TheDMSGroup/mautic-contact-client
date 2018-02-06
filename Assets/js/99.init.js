// General helpers for the Contact Client editor form.
Mautic.contactclientOnLoad = function () {
    mQuery(document).ready(function () {

        // Default behavior for Contact Client edit/details screens:
        if (mQuery('input[name="contactclient[type]"]').length) {
            Mautic.contactclientType();
            Mautic.contactclientExclusivity();
            Mautic.contactclientFilter();
            Mautic.contactclientLimits();
            Mautic.contactclientSchedule();
            Mautic.contactclientRevenue();
            return;
        }

        if (mQuery('#contactclient-timeline').length){
            Mautic.contactclientTimelineOnLoad();
        }
    });
};