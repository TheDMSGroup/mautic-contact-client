// API Payload field.
// API Payload JSON Schema.
Mautic.contactclientApiPayload = function () {
    mQuery(document).ready(function () {

        var $apiPayload = mQuery('#contactclient_api_payload');
        if (!window.contactclientApiPayloadLoaded && $apiPayload.length) {

            window.contactclientApiPayloadLoaded = true;

            var apiPayloadCodeMirror,
                apiPayloadJSONEditor;

            // Grab the JSON Schema to begin rendering the form with
            // JSONEditor.
            mQuery.ajax({
                dataType: 'json',
                cache: true,
                url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/api_payload.json',
                success: function (data) {
                    var schema = data;

                    // Create our widget container for the JSON Editor.
                    var $apiPayloadJSONEditor = mQuery('<div>', {
                        class: 'contactclient_jsoneditor'
                    }).insertBefore($apiPayload);

                    // Instantiate the JSON Editor based on our schema.
                    apiPayloadJSONEditor = new JSONEditor($apiPayloadJSONEditor[0], {
                        schema: schema
                    });

                    // Load the initial value if applicable.
                    var raw = $apiPayload.val(),
                        obj;
                    if (raw.length) {
                        try {
                            obj = mQuery.parseJSON(raw);
                            if (typeof obj === 'object') {
                                apiPayloadJSONEditor.setValue(obj);
                            }
                        }
                        catch (e) {
                            console.warn(e);
                        }
                    }

                    // Persist the value to the JSON Editor.
                    apiPayloadJSONEditor.on('change', function () {
                        var obj = apiPayloadJSONEditor.getValue();
                        if (typeof obj === 'object') {
                            var raw = JSON.stringify(obj, null, '  ');
                            if (raw.length) {
                                // Set the textarea.
                                $apiPayload.val(raw);
                            }
                            // Loop through operations to update success
                            // filters.
                            if (typeof obj.operations === 'object') {
                                for (var i = 0, leni = obj.operations.length; i < leni; i++) {
                                    // If there's a response object.
                                    if (typeof obj.operations[i].response === 'object') {
                                        var additionalFilters = [];

                                        // If there is a header array...
                                        if (typeof obj.operations[i].response.headers === 'object') {
                                            for (var j = 0, lenj = obj.operations[i].response.headers.length; j < lenj; j++) {
                                                // Grab the keys from each header field.
                                                if (typeof obj.operations[i].response.headers[j].key !== 'undefined' && obj.operations[i].response.headers[j].key.length) {
                                                    additionalFilters.push({
                                                        id: 'operation.' + i + '.headers.' + obj.operations[i].response.headers[j].key,
                                                        label: 'Header Field: ' + obj.operations[i].response.headers[j].key,
                                                        type: 'string',
                                                        operators: Mautic.contactclientSuccessDefinitionDefaultOps
                                                    });
                                                }
                                            }
                                        }

                                        // If there is a body array...
                                        if (typeof obj.operations[i].response.body === 'object') {
                                            for (var k = 0, lenk = obj.operations[i].response.body.length; k < lenk; k++) {
                                                // Grab the keys from each body field.
                                                if (typeof obj.operations[i].response.body[k].key !== 'undefined' && obj.operations[i].response.body[k].key.length) {
                                                    additionalFilters.push({
                                                        id: 'operation.' + i + '.body.' + obj.operations[i].response.body[k].key,
                                                        label: 'Body Field: ' + obj.operations[i].response.body[k].key,
                                                        type: 'string',
                                                        operators: Mautic.contactclientSuccessDefinitionDefaultOps
                                                    });
                                                }
                                            }
                                        }

                                        // If filters were found update the
                                        // query builder.
                                        if (additionalFilters.length) {
                                            var $queryBuilder = mQuery('#success-definition-' + i);
                                            if ($queryBuilder.length) {
                                                $queryBuilder.queryBuilder('setFilters', true, Mautic.contactclientSuccessDefinitionFiltersDefault.concat(additionalFilters));
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });

            // Gracefully enhance the API Payload widget with an Advanced
            // option using CodeMirror and JSON linting.
            var $apiPayloadCodeMirror = mQuery('<div>', {
                id: 'contactclient_api_payload_codemirror',
                class: 'hide'
            }).insertBefore($apiPayload);
            $apiPayload.css({'display': 'none'});
            apiPayloadCodeMirror = CodeMirror($apiPayloadCodeMirror[0], {
                value: $apiPayload.val(),
                mode: {
                    name: 'javascript',
                    json: true
                },
                theme: 'material',
                gutters: ['CodeMirror-lint-markers'],
                lint: 'json',
                lintOnChange: true,
                matchBrackets: true,
                autoCloseBrackets: true,
                lineNumbers: true,
                extraKeys: {'Ctrl-Space': 'autocomplete'},
                lineWrapping: true
            });
            apiPayloadCodeMirror.on('change', function () {
                // Set the value to the hidden textarea.
                var raw = apiPayloadCodeMirror.getValue();
                if (raw.length) {
                    // Always set the textarea.
                    $apiPayload.val(raw);
                }
            });

            // API Payload advanced button.
            mQuery('#api_payload_advanced .btn')
                .click(function () {
                    var raw = $apiPayload.val(),
                        error = false;
                    if (mQuery(this).hasClass('active')) {
                        // Deactivating CodeMirror.
                        // Send the value to JSONEditor.
                        if (apiPayloadJSONEditor) {
                            if (raw.length) {
                                try {
                                    var obj = mQuery.parseJSON(raw);
                                    if (typeof obj === 'object') {
                                        apiPayloadJSONEditor.setValue(obj);
                                    }
                                }
                                catch (e) {
                                    error = true;
                                    console.warn(e);
                                }
                            }
                            if (!error) {
                                $apiPayloadCodeMirror.addClass('hide');
                                mQuery('#contactclient_jsoneditor').removeClass('hide');
                            }
                            else {
                                mQuery(this).toggleClass('active');
                            }
                        }
                    }
                    else {
                        // Activating CodeMirror.
                        // Send the value from JSONEditor to
                        // CodeMirror.
                        if (apiPayloadCodeMirror) {
                            if (raw.length) {
                                try {
                                    if (raw !== apiPayloadCodeMirror.getValue()) {
                                        apiPayloadCodeMirror.setValue(raw, -1);
                                    }
                                }
                                catch (e) {
                                    error = true;
                                    console.warn('Error setting CodeMirror value.');
                                }
                            }
                            if (!error) {
                                $apiPayloadCodeMirror.removeClass('hide');
                                mQuery('#contactclient_jsoneditor').addClass('hide');
                                apiPayloadCodeMirror.refresh();
                            }
                            else {
                                mQuery(this).toggleClass('active');
                            }
                        }
                    }
                })
                // Since it's functional now, unhide the widget.
                .parent().parent().removeClass('hide');
        }
    });
};