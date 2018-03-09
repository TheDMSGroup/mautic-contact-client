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
            var obj;
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
                        var editorMode = function(el, format) {
                            format = format.toLowerCase();
                            setTimeout(function () {
                                var mode = 'text/html',
                                    lint = false;
                                if (format === 'json') {
                                    mode = {
                                        name: 'javascript',
                                        json: true
                                    };
                                    lint = 'json';
                                }
                                else if (format === 'yaml') {
                                    mode = 'yaml';
                                    lint = 'yaml';
                                }
                                else if (format === 'xml') {
                                    mode = 'xml';
                                    // Using the HTML lint here because the XML
                                    // Equivalent is 4+ MB in size, and not
                                    // worth the extra weight.
                                    lint = 'html';
                                } else if (format === 'html') {
                                    lint = 'html';
                                }
                                if (mode) {
                                    var $editor = el.parent().find('.CodeMirror:first');
                                    if ($editor.length) {
                                        var editor = $editor[0].CodeMirror;
                                        editor.setOption('mode', mode);
                                        editor.setOption('lint', false);
                                        editor.setOption('lint', lint);
                                    }
                                }
                            }, 200);
                        };
                        mQuery('div[data-schemapath*=".request.body"] select:not(.codemirror-checked)').change(function () {
                            var val = mQuery(this).val(),
                                el = mQuery(this);
                            if (val.toLowerCase() === 'raw') {
                                var $format = mQuery(this).parent().parent().parent().find('div[data-schemaid="requestFormat"]:first select');
                                if ($format.length) {
                                    var format = $format.val();
                                    editorMode(el, format);
                                    // Capture future changes of the format widget.
                                    $format.not('.format-checked').change(function(){
                                        editorMode(mQuery(this).parent().parent().parent(), mQuery(this).val())
                                    }).addClass('format-checked');
                                }
                            }
                        }).addClass('codemirror-checked');
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
            // Changed at runtime:
            mode: 'text/html',
            // Changed at runtime:
            lint: false,
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
                // Always set the textarea.
                $apiPayload.val(raw);
            }
        });

        var $buttons = mQuery('#api_payload_buttons'),
            $simpleView = $buttons.find('#api_payload_simple.btn'),
            $advancedView = $buttons.find('#api_payload_advanced.btn'),
            $codeView = $buttons.find('#api_payload_code.btn'),
            codeStart = function () {
                var raw = $apiPayload.val(),
                    error = false;
                // Activating CodeMirror.
                // Send the value from JSONEditor to
                // CodeMirror.
                if (!$codeView.hasClass('btn-success') && apiPayloadCodeMirror) {
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
                var raw = $apiPayload.val(),
                    error = false;
                // Deactivating CodeMirror.
                // Send the value to JSONEditor.
                if ($codeView.hasClass('btn-success') && apiPayloadJSONEditor) {
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
                            setJSONEditorValue(response.payload);
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