Mautic.contactclientOnLoad = function () {
    // Trigger tab visibility based on contactClient type.
    mQuery('input[name="contactclient[type]"]').change(function () {
        var val = mQuery(this).val();
        if (val === 'api') {
            mQuery('.api-payload').removeClass('hide');
            mQuery('.file-payload').addClass('hide');
            mQuery('.payload-tab').removeClass('hide');
        }
        else if (val === 'file') {
            mQuery('.api-payload').addClass('hide');
            mQuery('.file-payload').removeClass('hide');
            mQuery('.payload-tab').removeClass('hide');
        }
        else {
            mQuery('.api-payload').addClass('hide');
            mQuery('.file-payload').addClass('hide');
            mQuery('.payload-tab').addClass('hide');
        }
    }).first().parent().parent().find('label.active input:first').trigger('change');

};