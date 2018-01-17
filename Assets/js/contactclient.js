Mautic.contactclientgetIntegrationConfig = function (el, settings) {

    // @todo - trigger a lookup of the active destination campaigns on this custom client integration.
    if (mQuery(el).val()) {
        mQuery('#campaignevent_name').val('Push to ' + mQuery(el).parent().find('select option:selected').text());
    }

    Mautic.activateLabelLoadingIndicator(mQuery(el).attr('id'));

    if (typeof settings == 'undefined') {
        settings = {};
    }

    settings.name = mQuery(el).attr('name');
    var data = {integration: mQuery(el).val(), settings: settings};
    mQuery('.integration-campaigns-status').html('');
    mQuery('.integration-config-container').html('');

    Mautic.ajaxActionRequest('plugin:getIntegrationConfig', data,
        function (response) {
            if (response.success) {
                mQuery('.integration-config-container').html(response.html);
                Mautic.onPageLoad('.integration-config-container', response);
            }

            Mautic.integrationConfigOnLoad('.integration-config-container');
            Mautic.removeLabelLoadingIndicator();
        }
    );
};
alert('the contactclient.js file is being triggered globally.');