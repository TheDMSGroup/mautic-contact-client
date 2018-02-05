// @todo - File payload
Mautic.contactclientFilePayload = function () {
    var $filePayload = mQuery('#contactclient_file_payload');
    if (typeof window.contactclientFilePayloadLoaded === 'undefined' && $filePayload.length) {

        window.contactclientFilePayloadLoaded = true;
    }
};