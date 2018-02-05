// @todo - Exclusivity field.
Mautic.contactclientExclusivity = function () {
    var $exclusivity = mQuery('#contactclient_exclusivity');
    if (typeof window.contactclientExclusivityLoaded === 'undefined' && $exclusivity.length) {

        window.contactclientExclusivityLoaded = true;
    }
};