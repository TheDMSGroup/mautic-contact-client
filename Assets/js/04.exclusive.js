// Exclusive field.
Mautic.contactclientExclusive = function () {
    var $exclusive = mQuery('#contactclient_exclusive:first:not(.exclusive-checked)');
    if ($exclusive.length) {

        var exclusiveJSONEditor;

        // Grab the JSON Schema to begin rendering the form with JSONEditor.
        mQuery.ajax({
            dataType: 'json',
            cache: true,
            url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/exclusive.json',
            success: function (data) {
                var schema = data;

                // Create our widget container for the JSON Editor.
                var $exclusiveJSONEditor = mQuery('<div>', {
                    class: 'contactclient_jsoneditor'
                }).insertBefore($exclusive);

                // Instantiate the JSON Editor based on our schema.
                exclusiveJSONEditor = new JSONEditor($exclusiveJSONEditor[0], {
                    schema: schema,
                    disable_collapse: true
                });

                $exclusive.change(function () {
                    // Load the initial value if applicable.
                    var raw = mQuery(this).val(),
                        obj;
                    if (raw.length) {
                        try {
                            obj = mQuery.parseJSON(raw);
                            if (typeof obj === 'object') {
                                exclusiveJSONEditor.setValue(obj);
                            }
                        }
                        catch (e) {
                            console.warn(e);
                        }
                    }
                }).trigger('change');

                // Persist the value to the JSON Editor.
                exclusiveJSONEditor.on('change', function () {
                    var obj = exclusiveJSONEditor.getValue();
                    if (typeof obj === 'object') {
                        var raw = JSON.stringify(obj, null, 2);
                        if (raw.length) {
                            // Set the textarea.
                            $exclusive.val(raw);
                        }
                    }
                });

                $exclusiveJSONEditor.show();
            }
        });
        $exclusive.addClass('exclusive-checked');
    }
};