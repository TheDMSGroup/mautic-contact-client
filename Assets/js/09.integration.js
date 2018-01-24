// Integration screen logic. Triggered on Client change.
Mautic.contactclientIntegration = function () {
    var $client = mQuery('#campaignevent_properties_config_contactclient:first'),
        $overrides = mQuery('#campaignevent_properties_config_contactclient_overrides:first');
    if ($client.length && $overrides.length) {

        // Auto-set the integration name.
        var $client = mQuery('#campaignevent_properties_config_contactclient:first'),
            $eventName = mQuery('#campaignevent_name');
        if ($client.length && $client.val() && $eventName.length) {
            $eventName.val('Push contact to Client ' + $client.text().trim());
        }

        var clientId = parseInt($client.val());
        if (clientId) {
            var $select = mQuery('#campaignevent_properties_config_contactclient > option[value=' + clientId + ']:first'),
                json = $select.length ? $select.attr('data-overridable-fields') : null;

            // $overrides.val(json).removeClass('hide');
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

                // Load the initial value if applicable.
                var raw = $overrides.val(),
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

                // Persist the value to the JSON Editor.
                overridesJSONEditor.on('keyup', function () {
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

        // $overrides.css({'display': 'none'});
    }
};