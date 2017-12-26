Mautic.contactclientOnLoad = function () {
    // Trigger tab visibility based on contactClient type.
    mQuery('input[name="contactclient[type]"]').change(function () {
        var val = mQuery(this).val();
        if (val === 'api') {
            mQuery('.api-payload').removeClass('hide');
            mQuery('.file-payload').addClass('hide');
            console.log('api shown');
        }
        else if (val === 'file') {
            mQuery('.api-payload').addClass('hide');
            mQuery('.file-payload').removeClass('hide');
            console.log('file shown');
        }
        else {
            mQuery('.api-payload').addClass('hide');
            mQuery('.file-payload').addClass('hide');
            console.log('neither shown');
        }
    }).first().parent().parent().find('label.active input:first').trigger('change');

};