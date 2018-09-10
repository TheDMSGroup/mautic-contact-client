// General helpers for the Contact Client editor form.
Mautic.contactclientOnLoad = function () {
    // Edit screen:
    if (mQuery('input[name="contactclient[type]"]:first').length) {
        Mautic.contactclientType();
        Mautic.contactclientDuplicate();
        Mautic.contactclientExclusive();
        Mautic.contactclientFilter();
        Mautic.contactclientLimits();
        Mautic.contactclientSchedule();
        Mautic.contactclientAttribution();
        return;
    }

    // View screen:
    if (mQuery('.contactclient-timeline:first').length) {
        Mautic.contactclientTransactionsOnLoad();
        Mautic.contactclientEventsDatatable();
    }
};