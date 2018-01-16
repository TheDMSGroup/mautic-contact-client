// @todo - File payload
Mautic.contactclientFilePayload = function () {
    mQuery(document).ready(function () {

        var $filePayload = mQuery('#contactclient_file_payload');
        if (!window.contactclientFilePayloadLoaded && $filePayload.length) {

            window.contactclientFilePayloadLoaded = true;
        }
    });
};