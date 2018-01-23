// Integration screen logic. Triggered on Client change.
Mautic.contactclientIntegration = function () {
    var $client = mQuery('#campaignevent_properties_config_contactclient:first'),
        $overrides = mQuery('#campaignevent_properties_config_contactclient_overrides:first');
    if ($client.length && $overrides.length) {
        var clientId = parseInt($client.val());
        if (clientId) {
            var $select = mQuery('#campaignevent_properties_config_contactclient > option[value=' + clientId + ']:first'),
                json = $select.length ? $select.attr('data-overridable-fields') : null;
                // fields = (typeof json !== 'undefined' && json) ? mQuery.parseJSON(json) : null,
                // overrides = fields ? {'items': fields} : null;
            //
            // if (overrides) {
            //     $overrides.val(JSON.stringify(overrides));
            // }

            $overrides.val(json).removeClass('hide');
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
                    id: 'campaignevent_properties_config_contactclient_overrides_jsoneditor'
                }).insertBefore($overrides);

                // Instantiate the JSON Editor based on our schema.
                overridesJSONEditor = new JSONEditor($overridesJSONEditor[0], {
                    schema: schema
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
            }
        });

        // $overrides.css({'display': 'none'});
    }
};