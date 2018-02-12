// Duplicate field.
Mautic.contactclientDuplicate = function () {
    var $duplicate = mQuery('#contactclient_duplicate');
    if (typeof window.contactclientDuplicateLoaded === 'undefined' && $duplicate.length) {

        window.contactclientDuplicateLoaded = true;

        var duplicateJSONEditor;

        // Grab the JSON Schema to begin rendering the form with JSONEditor.
        mQuery.ajax({
            dataType: 'json',
            cache: true,
            url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/duplicate.json',
            success: function (data) {
                var schema = data;

                // Create our widget container for the JSON Editor.
                var $duplicateJSONEditor = mQuery('<div>', {
                    class: 'contactclient_jsoneditor'
                }).insertBefore($duplicate);

                // Instantiate the JSON Editor based on our schema.
                duplicateJSONEditor = new JSONEditor($duplicateJSONEditor[0], {
                    schema: schema,
                    disable_collapse: true
                });

                $duplicate.change(function () {
                    // Load the initial value if applicable.
                    var raw = mQuery(this).val(),
                        obj;
                    if (raw.length) {
                        try {
                            obj = mQuery.parseJSON(raw);
                            if (typeof obj === 'object') {
                                duplicateJSONEditor.setValue(obj);
                            }
                        }
                        catch (e) {
                            console.warn(e);
                        }
                    }
                }).trigger('change');

                // Persist the value to the JSON Editor.
                duplicateJSONEditor.on('change', function () {
                    var obj = duplicateJSONEditor.getValue();
                    if (typeof obj === 'object') {
                        var raw = JSON.stringify(obj, null, '  ');
                        if (raw.length) {
                            // Set the textarea.
                            $duplicate.val(raw);
                        }
                    }
                });

                $duplicate.addClass('hide');
                $duplicateJSONEditor.show();
                // mQuery('label[for=contactclient_duplicate]').addClass('hide');
            }
        });

    }
};