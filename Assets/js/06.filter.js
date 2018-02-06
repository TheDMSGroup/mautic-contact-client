// @todo - Filtering field.
Mautic.contactclientFilter = function () {
    var $filter = mQuery('#contactclient_filter');
    if (typeof window.contactclientFilterLoaded === 'undefined' && $filter.length) {

        window.contactclientFilterLoaded = true;
    }
};