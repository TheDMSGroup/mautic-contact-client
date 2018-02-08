// Exclusivity field.
Mautic.contactclientExclusivity = function () {
    var $exclusivity = mQuery('#contactclient_exclusivity');
    if (typeof window.contactclientExclusivityLoaded === 'undefined' && $exclusivity.length) {

        window.contactclientExclusivityLoaded = true;

        var exclusivityJSONEditor;

        // Grab the JSON Schema to begin rendering the form with JSONEditor.
        mQuery.ajax({
            dataType: 'json',
            cache: true,
            url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/exclusivity.json',
            success: function (data) {
                var schema = data;

                // Create our widget container for the JSON Editor.
                var $exclusivityJSONEditor = mQuery('<div>', {
                    class: 'contactclient_jsoneditor'
                }).insertBefore($exclusivity);

                // Instantiate the JSON Editor based on our schema.
                exclusivityJSONEditor = new JSONEditor($exclusivityJSONEditor[0], {
                    schema: schema,
                    disable_collapse: true
                });

                $exclusivity.change(function () {
                    // Load the initial value if applicable.
                    var raw = mQuery(this).val(),
                        obj;
                    if (raw.length) {
                        try {
                            obj = mQuery.parseJSON(raw);
                            if (typeof obj === 'object') {
                                exclusivityJSONEditor.setValue(obj);
                            }
                        }
                        catch (e) {
                            console.warn(e);
                        }
                    }
                }).trigger('change');

                // Persist the value to the JSON Editor.
                exclusivityJSONEditor.on('change', function () {
                    var obj = exclusivityJSONEditor.getValue();
                    if (typeof obj === 'object') {
                        var raw = JSON.stringify(obj, null, '  ');
                        if (raw.length) {
                            // Set the textarea.
                            $exclusivity.val(raw);
                        }
                    }
                });

                $exclusivity.addClass('hide');
                $exclusivityJSONEditor.show();
                mQuery('label[for=contactclient_exclusivity]').addClass('hide');
            }
        });

    }
};