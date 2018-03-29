// API Payload field.
// API Payload JSON Schema.
Mautic.contactclientApiPayloadPre = function () {
    var $apiPayload = mQuery('#contactclient_api_payload:first:not(.hide):not(.payload-checked)');
    if ($apiPayload.length) {

        var tokenSource = 'plugin:mauticContactClient:getTokens';
        if (typeof window.JSONEditor.tokenCache === 'undefined') {
            window.JSONEditor.tokenCache = {};
        }
        window.JSONEditor.tokenCache[tokenSource] = {};
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: 'POST',
            data: {
                action: tokenSource
            },
            cache: true,
            dataType: 'json',
            success: function (response) {
                if (typeof response.tokens !== 'undefined') {
                    window.JSONEditor.tokenCache[tokenSource] = response.tokens;
                }
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
            },
            complete: function () {
                Mautic.contactclientApiPayload();
            }
        });
        $apiPayload.addClass('payload-checked');

        // Ensure our affix nav functions even on ajax create.
        mQuery('#api_payload_buttons[data-spy="affix"]').each(function () {
            mQuery(this).affix({
                offset: {
                    top: mQuery(this).attr('data-offset-top')
                }
            });
        });
    }
};
Mautic.contactclientApiPayload = function () {

    var $apiPayload = mQuery('#contactclient_api_payload:first:not(.hide)');
    if ($apiPayload.length) {

        var apiPayloadCodeMirror,
            apiPayloadJSONEditor;

        function setJSONEditorValue (raw) {
            var result = true;
            if (raw.length) {
                try {
                    var obj = mQuery.parseJSON(raw);
                    if (typeof obj === 'object') {
                        if (apiPayloadJSONEditor.getValue() !== obj) {
                            // console.log('Set value to JSON editor');
                            apiPayloadJSONEditor.setValue(obj);
                        }
                    }
                }
                catch (e) {
                    console.warn(e);
                    result = false;
                }
            }
            return result;
        }

        // Grab the JSON Schema to begin rendering the form with JSONEditor.
        mQuery.ajax({
            dataType: 'json',
            cache: true,
            url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/api_payload.json',
            success: function (data) {
                var schema = data;

                // Pre-load our field tokens for destination dropdowns.
                var tokenSource = 'plugin:mauticContactClient:getTokens';
                if (typeof window.JSONEditor.tokenCache[tokenSource] !== 'undefined'
                    && !mQuery.isEmptyObject(window.JSONEditor.tokenCache[tokenSource])
                ) {
                    var sources = [
                        {
                            title: '',
                            value: ''
                        },
                        {
                            title: '-- None --',
                            value: ''
                        }
                    ];
                    mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                        sources.push({
                            title: value,
                            value: key
                        });
                    });
                    schema.definitions.responseField.properties.destination.enumSource = [
                        {
                            'source': sources,
                            'title': '{{item.title}}',
                            'value': '{{item.value}}'
                        }
                    ];
                }

                // Create our widget container for the JSON Editor.
                var $apiPayloadJSONEditor = mQuery('<div>', {
                    class: 'contactclient_jsoneditor',
                    id: 'api_payload_jsoneditor'
                }).insertBefore($apiPayload);

                // Instantiate the JSON Editor based on our schema.
                apiPayloadJSONEditor = new JSONEditor($apiPayloadJSONEditor[0], {
                    schema: schema
                });

                // Load the initial value if applicable.
                setJSONEditorValue($apiPayload.val());

                // Persist the value to the JSON Editor.
                apiPayloadJSONEditor.off('change').on('change', function () {
                    // console.log('JSON editor change event');
                    var obj = apiPayloadJSONEditor.getValue();
                    if (typeof obj === 'object') {
                        var raw = JSON.stringify(obj, null, 2);
                        if (raw.length && $apiPayload.val() !== raw) {
                            // console.log('Change by JSON editor', raw);
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
                                            // Grab the keys from each
                                            // header field.
                                            if (typeof obj.operations[i].response.headers[j].key !== 'undefined' && obj.operations[i].response.headers[j].key.length) {
                                                additionalFilters.push({
                                                    id: 'headers.' + obj.operations[i].response.headers[j].key,
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
                                            // Grab the keys from each body
                                            // field.
                                            if (typeof obj.operations[i].response.body[k].key !== 'undefined' && obj.operations[i].response.body[k].key.length) {
                                                additionalFilters.push({
                                                    id: 'body.' + obj.operations[i].response.body[k].key,
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
                                            $queryBuilder.queryBuilder('setFilters', true, Mautic.successDefinitionFiltersDefault.concat(additionalFilters));
                                        }
                                    }
                                }
                            }
                        }

                        // Apply tokenization if available.
                        if (typeof Mautic.contactclientTokens === 'function') {
                            Mautic.contactclientTokens();
                        }

                        // Apply CodeMirror typecasting.
                        var lastFormat, initialFormat;
                        var editorMode = function ($template, format) {
                                format = format.toLowerCase();
                                setTimeout(function () {
                                    if (format === lastFormat && format !== initialFormat) {
                                        return;
                                    }
                                    var $cm = $template.find('div.CodeMirror-wrap:first');
                                    if ($cm.length && typeof $cm[0].CodeMirror !== 'undefined') {
                                        var mode = 'text/html/mustache',
                                            lint = false,
                                            cm = $cm[0].CodeMirror;
                                        if (format === 'json') {
                                            // mode = {
                                            //     name: 'javascript',
                                            //     json: true
                                            // };
                                            mode = 'json/mustache';
                                            lint = 'json';
                                        }
                                        else if (format === 'yaml') {
                                            mode = 'yaml/mustache';
                                            lint = 'yaml';
                                        }
                                        else if (format === 'xml') {
                                            mode = 'xml/mustache';
                                            // Using the HTML lint here because
                                            // the XML Equivalent is 4+ MB in
                                            // size, and not worth the extra
                                            // weight.
                                            lint = 'html';
                                        }
                                        else if (format === 'html') {
                                            lint = 'html';
                                        }
                                        cm.setOption('lint', false);
                                        cm.setOption('mode', mode);
                                        cm.setOption('lint', lint);

                                        // root[operations][0][request][template]
                                        var regex = /root\[operations\]\[(\d+)\]\[request\]\[template\]/,
                                            match;
                                        if ((match = regex.exec($template.find('textarea:first').attr('name'))) !== null && typeof match[1] === 'string') {
                                            var cmValue = cm.getValue(),
                                                fields = requestBodyFields(match[1]);
                                            if (cmValue.length === 0 || (lastFormat && cmValue === boilerPlate(fields, lastFormat))) {
                                                cm.setValue(boilerPlate(fields, format));
                                                CodeMirror.signal(cm, 'change', cm);
                                            }
                                        }
                                    }
                                    if (!initialFormat) {
                                        initialFormat = format;
                                    }
                                    lastFormat = format;
                                }, 200);
                            },
                            // Boilerplate template generation.
                            boilerPlate = function (fields, format) {
                                var output = '',
                                    set = [];
                                if (format === 'json') {
                                    output += JSON.stringify(fields, null, 2);
                                    output = output.replace(/"true"/ig, 'true').replace(/"false"/ig, 'false');
                                }
                                else if (format === 'xml') {
                                    output += '<?xml version="1.0" encoding="UTF-8"?>\n';
                                    output += '<contact>\n';
                                    mQuery.each(fields, function (key, value) {
                                        output += '    <' + key + '>' + value + '</' + key + '>\n';
                                    });
                                    output += '</contact>';
                                }
                                else if (format === 'html') {
                                    output += '<!DOCTYPE html>\n';
                                    output += '<html>\n';
                                    output += '    <head>\n';
                                    output += '        <meta charset="UTF-8">\n';
                                    output += '        <title>Contact</title>\n';
                                    output += '    </head>\n';
                                    output += '    <body>\n';
                                    mQuery.each(fields, function (key, value) {
                                        output += '        <div class="' + key + '">' + value + '</div>\n';
                                    });
                                    output += '    </body>\n';
                                    output += '</html>';
                                }
                                else if (format === 'form') {
                                    mQuery.each(fields, function (key, value) {
                                        // Do NOT encode mustache tags, since
                                        // they will not be included in the
                                        // output.
                                        set.push(encodeURIComponent(key) + '=' + value);
                                    });
                                    output += set.join('&');
                                }
                                else if (format === 'yaml') {
                                    output += 'contact:\n';
                                    mQuery.each(fields, function (key, value) {
                                        set.push('    ' + key + ': \'' + value.replace('\'', '\'\'') + '\'');
                                    });
                                    output += set.join('\n');
                                }
                                else if (format === 'text') {
                                    mQuery.each(fields, function (key, value) {
                                        set.push(key + ': ' + value);
                                    });
                                    output += set.join('\n');
                                }
                                return output;
                            },
                            // Get a key/value array of body fields from an
                            // operationID.
                            requestBodyFields = function (i) {
                                var obj = apiPayloadJSONEditor.getValue(),
                                    fields = {};
                                if (typeof obj === 'object') {
                                    if (
                                        typeof obj.operations === 'object'
                                        && typeof obj.operations[i].request === 'object'
                                        && typeof obj.operations[i].request.body === 'object'
                                    ) {
                                        for (var k = 0, lenk = obj.operations[i].request.body.length; k < lenk; k++) {
                                            if (
                                                typeof obj.operations[i].request.body[k].key !== 'undefined'
                                                && obj.operations[i].request.body[k].key.length
                                                && typeof obj.operations[i].request.body[k].value !== 'undefined'
                                                && obj.operations[i].request.body[k].value.length
                                            ) {
                                                fields[obj.operations[i].request.body[k].key] = obj.operations[i].request.body[k].value;
                                            }
                                        }
                                    }
                                }
                                return fields;
                            },
                            // requestTemplate = function (i) {
                            //     var obj = apiPayloadJSONEditor.getValue(),
                            //         template = '';
                            //     if (typeof obj === 'object') {
                            //         if (
                            //             typeof obj.operations === 'object'
                            //             && typeof obj.operations[i].request
                            // === 'object' && typeof
                            // obj.operations[i].request.template === 'object'
                            // ) { template =
                            // obj.operations[i].request.template; } } return
                            // template; },
                            requestBodyFieldsUpdate = function (i, keepers, additions) {
                                var obj = apiPayloadJSONEditor.getValue(),
                                    changes = false;
                                if (typeof obj === 'object') {
                                    if (
                                        typeof obj.operations === 'object'
                                        && typeof obj.operations[i].request === 'object'
                                        && typeof obj.operations[i].request.body === 'object'
                                    ) {
                                        // Remove any (by value) not in the
                                        // keepers array.
                                        var fields = [],
                                            fieldValues = [];
                                        for (var k = 0, lenk = obj.operations[i].request.body.length; k < lenk; k++) {
                                            if (
                                                typeof obj.operations[i].request.body[k].value === 'undefined'
                                                || keepers.indexOf(obj.operations[i].request.body[k].value) === -1
                                            ) {
                                                changes = true;
                                            }
                                            else if (fieldValues.indexOf(obj.operations[i].request.body[k].value) === -1) {
                                                // Prevent duplicates.
                                                fields.push(obj.operations[i].request.body[k]);
                                                fieldValues.push(obj.operations[i].request.body[k].value);
                                            }
                                        }

                                        // Add new fields.
                                        if (additions.length) {
                                            mQuery.each(additions, function (key, value) {
                                                fields.push({
                                                    'key': value.replace('{{', '').replace('}}', ''),
                                                    'value': value,
                                                    'default_value': '',
                                                    'test_value': '',
                                                    'test_only': false,
                                                    'description': '',
                                                    'overridable': false,
                                                    'required': false
                                                });
                                                changes = true;
                                            });
                                        }

                                        // Sort the resulting fields by value.
                                        fields.sort(function (a, b) {
                                            a = a.value.toLowerCase();
                                            b = b.value.toLowerCase();
                                            if (a < b) {
                                                return -1;
                                            }
                                            if (a > b) {
                                                return 1;
                                            }
                                            return 0;
                                        });

                                        // Update the JSONEditor schema value.
                                        if (changes) {
                                            // console.log('Update the
                                            // JSONEditor schema value.');
                                            var subEditor = apiPayloadJSONEditor.getEditor('root.operations.' + i + '.request.body');
                                            // Setting to a null value to cause
                                            // re-instantiation of tag-editor.
                                            subEditor.setValue([]);
                                            subEditor.setValue(fields);
                                        }
                                    }
                                }
                            };
                        mQuery('div[data-schematype="boolean"][data-schemapath*=".request.manual"] input[type="checkbox"]:not(.manual-checked)').off('change').on('change', function () {
                            var $this = mQuery(this),
                                val = $this.is(':checked'),
                                $container = $this.parent().parent().parent().parent().parent(),
                                $body = $container.find('div[data-schematype="array"][data-schemapath*=".request.body"]:first'),
                                $template = $container.find('div[data-schematype="string"][data-schemapath*=".request.template"]:first'),
                                $textarea = $template.find('textarea:first:not(.template-checked)'),
                                $format = $container.find('div[data-schemaid="requestFormat"]:first:not(.format-checked) select:first');

                            if ($template.length) {
                                if (val) {
                                    if ($format.length) {
                                        $format.off('change').on('change', function () {
                                            editorMode($template, mQuery(this).val());
                                        }).trigger('change').addClass('format-checked');
                                    }
                                    $template.show();
                                    $body.addClass('manual');
                                }
                                else {
                                    $template.hide();
                                    $body.removeClass('manual');
                                }
                            }

                            // Set up changes to the textarea to trickle to
                            // field settings.
                            var templateChange,
                                nameRegex = /root\[operations\]\[(\d+)\]\[request\]\[template\]/,
                                tokenRegex = /{{\s*?[\w\.]+\s*}}/g,
                                match,
                                operation = 0,
                                previousKeepers = [],
                                previousAdditions = [];
                            if ((match = nameRegex.exec($textarea.attr('name'))) !== null && typeof match[1] === 'string') {
                                operation = match[1];
                            }

                            $textarea.off('change').on('change', function () {
                                // var value = requestTemplate(operation);
                                var textarea = mQuery(this);
                                clearInterval(templateChange);
                                templateChange = setTimeout(function () {
                                    if (typeof textarea === 'undefined') {
                                        return false;
                                    }
                                    var value = textarea.val();
                                    if (typeof value !== 'undefined' && value.length) {
                                        // Find all Mustache tokens from
                                        // content, ignore openers/closers.
                                        var TemplateTokens = value.match(tokenRegex);

                                        // Do nothing if not matches are
                                        // found, assume we are in error.
                                        if (typeof TemplateTokens !== 'undefined' && TemplateTokens && TemplateTokens.length) {
                                            // Find the request fields to
                                            // compare.
                                            // root[operations][0][request][template]
                                            var keepers = [],
                                                additions = [],
                                                fields = requestBodyFields(operation);

                                            mQuery.each(fields, function (key, val) {
                                                // Find tokens within
                                                // "value" because there
                                                // could be multiple.
                                                var fieldTokens = val.match(tokenRegex);
                                                if (typeof fieldTokens === 'object' && fieldTokens) {
                                                    // Compare against
                                                    // tokens found in the
                                                    // template.
                                                    mQuery.each(fieldTokens, function (fieldKey, fieldToken) {
                                                        if (TemplateTokens.indexOf(fieldToken) !== -1) {
                                                            // This field
                                                            // has a
                                                            // purpose,
                                                            // leave it.
                                                            keepers.push(val);
                                                            return false;
                                                        }
                                                    });
                                                }
                                            });

                                            // For each valToken that isn't
                                            // in keepers, we need to add a
                                            // field.
                                            mQuery.each(TemplateTokens, function (TemplateKey, TemplateToken) {
                                                if (keepers.indexOf(TemplateToken) === -1) {
                                                    additions.push(TemplateToken);
                                                }
                                            });

                                            // Update fields as necessary.
                                            if (
                                                keepers !== previousKeepers
                                                || previousAdditions !== additions
                                            ) {
                                                requestBodyFieldsUpdate(operation, keepers, additions);
                                                previousKeepers = keepers;
                                                previousAdditions = additions;
                                            }
                                        }
                                    }
                                }, 500);
                            }).addClass('template-checked');
                        }).addClass('manual-checked').trigger('change');
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
            lint: 'json',
            theme: 'material',
            gutters: ['CodeMirror-lint-markers'],
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
                // console.log('Change by codemirror', raw);
                $apiPayload.val(raw);
            }
        });

        var $buttons = mQuery('#api_payload_buttons'),
            $simpleView = $buttons.find('#api_payload_simple.btn'),
            $advancedView = $buttons.find('#api_payload_advanced.btn'),
            $codeView = $buttons.find('#api_payload_code.btn'),
            codeStart = function () {
                // Activating CodeMirror.
                // Send the value from JSONEditor to
                // CodeMirror.
                if (!$codeView.hasClass('btn-success') && apiPayloadCodeMirror) {
                    var raw = $apiPayload.val(),
                        error = false;
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
                        mQuery('#api_payload_jsoneditor').addClass('hide');
                        apiPayloadCodeMirror.refresh();
                        mQuery(this).toggleClass('btn-success');
                    }
                    else {
                        mQuery(this).toggleClass('active');
                    }
                }
            },
            codeStop = function () {
                // Deactivating CodeMirror.
                // Send the value to JSONEditor.
                if ($codeView.hasClass('btn-success') && apiPayloadJSONEditor) {
                    if (setJSONEditorValue($apiPayload.val())) {
                        $apiPayloadCodeMirror.addClass('hide');
                        mQuery('#api_payload_jsoneditor').removeClass('hide');
                        mQuery(this).toggleClass('btn-success');
                    }
                    else {
                        mQuery(this).toggleClass('active');
                    }
                }
            },
            viewMode = function (mode) {
                if (mode === 'simple') {
                    codeStop();
                    $simpleView.addClass('btn-success');
                    $advancedView.removeClass('btn-success');
                    $codeView.removeClass('btn-success');
                    mQuery('#api_payload_jsoneditor').removeClass('advanced');
                }
                else if (mode === 'advanced') {
                    codeStop();
                    $advancedView.addClass('btn-success');
                    $simpleView.removeClass('btn-success');
                    $codeView.removeClass('btn-success');
                    mQuery('#api_payload_jsoneditor').addClass('advanced');
                }
                else if (mode === 'code') {
                    codeStart();
                    $codeView.addClass('btn-success');
                    $simpleView.removeClass('btn-success');
                    $advancedView.removeClass('btn-success');
                    mQuery('#api_payload_jsoneditor').removeClass('advanced');
                }
                $buttons.find('.active').removeClass('active');
            };

        // API Payload simple button.
        $simpleView
            .click(function () {
                viewMode('simple');
            });

        // API Payload advanced button.
        $advancedView
            .click(function () {
                viewMode('advanced');
            });

        // API Payload code button.
        $codeView
            .click(function () {
                viewMode('code');
            })
            // Since it's functional now, unhide the widget.
            .parent().parent().removeClass('hide');

        /**
         * Test Ajax.
         */
        var apiPayloadTestCodeMirror;
        mQuery('#api_payload_test').click(function () {
            var $button = mQuery(this),
                $resultContainer = mQuery('#api_payload_test_result'),
                $result = $resultContainer.find('#api_payload_test_result_yaml'),
                $attributionDefault = mQuery('#contactclient_attribution_default:first'),
                $attributionSettings = mQuery('#contactclient_attribution_settings:first');
            if ($button.hasClass('active')) {
                // Test Deactivation.
            }
            else {
                // Test Activation.
                var data = {
                    action: 'plugin:mauticContactClient:getApiPayloadTest',
                    apiPayload: $apiPayload.val(),
                    attributionDefault: $attributionDefault.length ? $attributionDefault.val() : '',
                    attributionSettings: $attributionSettings.length ? $attributionSettings.val() : ''
                };
                $resultContainer.addClass('hide');
                $result.addClass('hide');
                mQuery.ajax({
                    url: mauticAjaxUrl,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        if (typeof response.html !== 'undefined') {
                            $resultContainer.removeClass('hide');
                            $result.removeClass('hide');

                            // sends markup through core js parsers
                            if (response.html !== '') {
                                if (!apiPayloadTestCodeMirror) {
                                    apiPayloadTestCodeMirror = CodeMirror($result[0], {
                                        value: response.html,
                                        mode: 'yaml',
                                        theme: 'material',
                                        gutters: [],
                                        lineNumbers: false,
                                        lineWrapping: true,
                                        readOnly: true
                                    });
                                }
                                else {
                                    apiPayloadTestCodeMirror.setValue(response.html, -1);
                                }
                                Mautic.onPageLoad('#api_payload_test_result', response);
                            }
                        }
                        if (typeof response.payload !== 'undefined' && response.payload.length && typeof setJSONEditorValue === 'function') {
                            var raw = JSON.stringify(response.payload, null, 2);
                            if (raw.length && $apiPayload.val() !== raw) {
                                // console.log('Change by payload', raw);
                                $apiPayload.val(raw);
                                setJSONEditorValue($apiPayload.val());
                            }
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    },
                    complete: function () {
                        Mautic.removeButtonLoadingIndicator($button);
                        mQuery('html, body').stop().animate({
                            scrollTop: $resultContainer.first().offset().top
                        }, 500);
                        $button.removeClass('active');
                    }
                });
            }
        });
    }
};