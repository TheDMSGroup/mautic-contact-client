// Integration screen logic. Triggered on Client change.
Mautic.contactclientIntegration = function () {
    var $client = mQuery('#campaignevent_properties_config_contactclient:first'),
        $overrides = mQuery('#campaignevent_properties_config_contactclient_overrides:first');
    if ($client.length && $overrides.length) {

        // Get the JSON from the client selector, if not already present.
        $client.change(function(){
            var clientId = parseInt(mQuery(this).val()),
                $select = clientId ? mQuery('#campaignevent_properties_config_contactclient > option[value=' + clientId + ']:first') : [],
                json = $select.length ? $select.attr('data-overridable-fields') : null;

            if (json) {
                mQuery('#campaignevent_properties_config_contactclient_overrides_button').removeClass('hide');
                mQuery('label[for=campaignevent_properties_config_contactclient_overrides]:first, .contactclient_jsoneditor').addClass('hide');
            } else {
                mQuery('#campaignevent_properties_config_contactclient_overrides_button').addClass('hide');
                mQuery('label[for=campaignevent_properties_config_contactclient_overrides]:first, .contactclient_jsoneditor').addClass('hide');
            }

            $overrides.val(json);
        });
        if ($overrides.val().length === 0) {
            $client.trigger('change');
        }

        var overridesJSONEditor;

        // Grab the JSON Schema to begin rendering the form with
        // JSONEditor.
        mQuery.ajax({
            dataType: 'json',
            cache: true,
            url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/overrides.json',
            success: function (data) {
                var schema = data;

                // Create our widget container for the JSON Editor.
                var $overridesJSONEditor = mQuery('<div>', {
                    class: 'contactclient_jsoneditor'
                }).insertBefore($overrides);

                // Instantiate the JSON Editor based on our schema.
                overridesJSONEditor = new JSONEditor($overridesJSONEditor[0], {
                    schema: schema,
                    disable_array_add: true,
                    disable_array_delete: true,
                    disable_array_reorder: true,
                    disable_collapse: true
                });

                $overrides.change(function(){
                    // Load the initial value if applicable.
                    var raw = mQuery(this).val(),
                        obj;
                    if (raw.length) {
                        try {
                            obj = mQuery.parseJSON(raw);
                            if (typeof obj === 'object') {
                                overridesJSONEditor.setValue(obj);
                            }
                        }
                        catch (e) {
                            console.warn(e);
                        }
                    }
                }).trigger('change');

                // Persist the value to the JSON Editor.
                overridesJSONEditor.on('change', function () {
                    var obj = overridesJSONEditor.getValue();
                    if (typeof obj === 'object') {
                        var raw = JSON.stringify(obj, null, '  ');
                        if (raw.length) {
                            // Set the textarea.
                            $overrides.val(raw);
                        }
                    }
                });
                // Hide the button, show the label.
                mQuery('#campaignevent_properties_config_contactclient_overrides_button').addClass('hide');
                mQuery('label[for=campaignevent_properties_config_contactclient_overrides]').removeClass('hide');
            }
        });
    }
};