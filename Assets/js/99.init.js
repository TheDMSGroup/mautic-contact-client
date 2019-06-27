// General helpers for the Contact Client editor form.
Mautic.contactclientOnLoad = function () {
    if (mQuery('input[name="contactclient[type]"]').length) {
        // Client edit screen.
        Mautic.contactclientType();
        Mautic.contactclientDuplicate();
        Mautic.contactclientExclusive();
        Mautic.contactclientFilter();
        Mautic.contactclientLimits();
        Mautic.contactclientSchedule();
        Mautic.contactclientAttribution();
    }
    else {
        // Client view screen.
        Mautic.contactclientEventsDatatable();
        Mautic.contactclientTransactionsTable();
        Mautic.contactclientPendingEventsTable();
    }
};