// Attribition (cost/revenue/finances)
Mautic.contactclientAttribution = function () {
    var $attribution = mQuery('#contactclient_attribution_settings:first:not(.attribution-checked)');
    if ($attribution.length) {

        /**
         * Get all keys/fields from the API Payload responses.
         * @returns {Array}
         */
        var getApiPayloadFields = function () {
            var keys = {},
                obj,
                $apiPayload = mQuery('#contactclient_api_payload');
            if ($apiPayload.length) {
                var raw = $apiPayload.val();
                if (raw.length) {
                    try {
                        obj = mQuery.parseJSON(raw);
                        if (typeof obj === 'object') {
                            if (typeof obj.operations === 'object') {
                                for (var i = 0, leni = obj.operations.length; i < leni; i++) {
                                    // If there's a response object.
                                    if (typeof obj.operations[i].response === 'object') {

                                        // If there is a header array...
                                        if (typeof obj.operations[i].response.headers === 'object') {
                                            for (var j = 0, lenj = obj.operations[i].response.headers.length; j < lenj; j++) {
                                                if (typeof obj.operations[i].response.headers[j].key !== 'undefined' && obj.operations[i].response.headers[j].key.length) {
                                                    keys[obj.operations[i].response.headers[j].key] = obj.operations[i].response.headers[j].example;
                                                }
                                            }
                                        }

                                        // If there is a body array...
                                        if (typeof obj.operations[i].response.body === 'object') {
                                            for (var k = 0, lenk = obj.operations[i].response.body.length; k < lenk; k++) {
                                                if (typeof obj.operations[i].response.body[k].key !== 'undefined' && obj.operations[i].response.body[k].key.length) {
                                                    keys[obj.operations[i].response.body[k].key] = obj.operations[i].response.body[k].example;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    catch (e) {
                        console.warn(e);
                    }
                }
            }

            // Sort our keys alphabetically (already de-duped).
            var keyKeys = Object.keys(keys),
                result = [];
            keyKeys.sort();
            for (var l = 0, keyKeyslen = keyKeys.length; l < keyKeyslen; l++) {
                result[keyKeys[l]] = keys[keyKeys[l]];
            }
            keys = result;

            // Convert our keys to the enum structure needed.
            var source = [], key;
            for (key in keys) {
                if (keys.hasOwnProperty(key)) {
                    if (keys[key].length) {
                        source.push({
                            'value': key,
                            'title': key + ' (' + keys[key] + ')'
                        });
                    }
                    else {
                        source.push({
                            'value': key,
                            'title': key
                        });
                    }
                }
            }

            return [
                {
                    'source': source,
                    'title': '{{item.title}}',
                    'value': '{{item.value}}'
                }
            ];
        };

        var attributionJSONEditor;

        // Grab the JSON Schema to begin rendering the form with JSONEditor.
        mQuery.ajax({
            dataType: 'json',
            cache: true,
            url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/attribution.json',
            success: function (data) {
                var schema = data;
                // Insert possible API fields from API Payload.
                schema.properties.mode.oneOf[1].properties.key.enumSource = getApiPayloadFields();

                // Create our widget container for the JSON Editor.
                var $attributionJSONEditor = mQuery('<div>', {
                    class: 'contactclient_jsoneditor'
                }).insertBefore($attribution);

                // Instantiate the JSON Editor based on our schema.
                attributionJSONEditor = new JSONEditor($attributionJSONEditor[0], {
                    schema: schema,
                    disable_collapse: true
                });

                $attribution.change(function () {
                    // Load the initial value if applicable.
                    var raw = mQuery(this).val(),
                        obj;
                    if (raw.length) {
                        try {
                            obj = mQuery.parseJSON(raw);
                            if (typeof obj === 'object') {
                                attributionJSONEditor.setValue(obj);
                            }
                        }
                        catch (e) {
                            console.warn(e);
                        }
                    }
                }).trigger('change');

                // Persist the value to the JSON Editor.
                attributionJSONEditor.on('change', function () {
                    var obj = attributionJSONEditor.getValue();
                    if (typeof obj === 'object') {
                        var raw = JSON.stringify(obj, null, 2);
                        if (raw.length) {
                            // Set the textarea.
                            $attribution.val(raw);
                        }
                    }
                });

                $attribution.addClass('attribution-checked');
                $attributionJSONEditor.show();
                mQuery('label[for=contactclient_attribution_settings]').addClass('hide');
            }
        });
    }
};
