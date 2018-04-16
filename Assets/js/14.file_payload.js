// API Payload field.
// API Payload JSON Schema.
Mautic.contactclientFilePayloadPre = function () {
    var $filePayload = mQuery('#contactclient_file_payload:first:not(.hide):not(.payload-checked)');
    if ($filePayload.length) {

        var tokenSource = 'plugin:mauticContactClient:getTokens';
        if (typeof window.JSONEditor.tokenCache === 'undefined') {
            window.JSONEditor.tokenCache = {};
        }
        if (typeof window.JSONEditor.tokenCache[tokenSource] === 'undefined') {
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
                    Mautic.contactclientFilePayload();
                }
            });
        } else {
            Mautic.contactclientFilePayload();
        }
        $filePayload.addClass('payload-checked');

        // Ensure our affix nav functions even on ajax create.
        mQuery('#file_payload_buttons[data-spy="affix"]').each(function () {
            mQuery(this).affix({
                offset: {
                    top: mQuery(this).attr('data-offset-top')
                }
            });
        });
    }
};
Mautic.contactclientFilePayload = function () {

    var $filePayload = mQuery('#contactclient_file_payload:first:not(.hide)');
    if ($filePayload.length) {

        var filePayloadCodeMirror,
            filePayloadJSONEditor;

        function setJSONEditorValue (raw) {
            var result = true;
            if (raw.length) {
                try {
                    var obj = mQuery.parseJSON(raw);
                    if (typeof obj === 'object') {
                        if (filePayloadJSONEditor.getValue() !== obj) {
                            // console.log('Set value to JSON editor');
                            filePayloadJSONEditor.setValue(obj);
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
            url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/file_payload.json',
            success: function (data) {
                var schema = data;

                // Create our widget container for the JSON Editor.
                var $filePayloadJSONEditor = mQuery('<div>', {
                    class: 'contactclient_jsoneditor',
                    id: 'file_payload_jsoneditor'
                }).insertBefore($filePayload);

                // Instantiate the JSON Editor based on our schema.
                filePayloadJSONEditor = new JSONEditor($filePayloadJSONEditor[0], {
                    schema: schema
                });

                // Load the initial value if applicable.
                setJSONEditorValue($filePayload.val());

                // Persist the value to the JSON Editor.
                filePayloadJSONEditor.off('change').on('change', function () {
                    // console.log('JSON editor change event');
                    var obj = filePayloadJSONEditor.getValue();
                    if (typeof obj === 'object') {
                        var raw = JSON.stringify(obj, null, 2);
                        if (raw.length && $filePayload.val() !== raw) {
                            // console.log('Change by JSON editor', raw);
                            $filePayload.val(raw);
                        }
                    }
                }).trigger('change');
            }
        });

        // Gracefully enhance the API Payload widget with an Advanced
        // option using CodeMirror and JSON linting.
        var $filePayloadCodeMirror = mQuery('<div>', {
            id: 'contactclient_file_payload_codemirror',
            class: 'hide'
        }).insertBefore($filePayload);
        $filePayload.css({'display': 'none'});
        filePayloadCodeMirror = CodeMirror($filePayloadCodeMirror[0], {
            value: $filePayload.val(),
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
        filePayloadCodeMirror.on('change', function () {
            // Set the value to the hidden textarea.
            var raw = filePayloadCodeMirror.getValue();
            if (raw.length) {
                // console.log('Change by codemirror', raw);
                $filePayload.val(raw);
            }
        });

        var $buttons = mQuery('#file_payload_buttons'),
            $simpleView = $buttons.find('#file_payload_simple.btn'),
            $advancedView = $buttons.find('#file_payload_advanced.btn'),
            $codeView = $buttons.find('#file_payload_code.btn'),
            codeStart = function () {
                // Activating CodeMirror.
                // Send the value from JSONEditor to
                // CodeMirror.
                if (!$codeView.hasClass('btn-success') && filePayloadCodeMirror) {
                    var raw = $filePayload.val(),
                        error = false;
                    if (raw.length) {
                        try {
                            if (raw !== filePayloadCodeMirror.getValue()) {
                                filePayloadCodeMirror.setValue(raw, -1);
                            }
                        }
                        catch (e) {
                            error = true;
                            console.warn('Error setting CodeMirror value.');
                        }
                    }
                    if (!error) {
                        $filePayloadCodeMirror.removeClass('hide');
                        mQuery('#file_payload_jsoneditor').addClass('hide');
                        filePayloadCodeMirror.refresh();
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
                if ($codeView.hasClass('btn-success') && filePayloadJSONEditor) {
                    if (setJSONEditorValue($filePayload.val())) {
                        $filePayloadCodeMirror.addClass('hide');
                        mQuery('#file_payload_jsoneditor').removeClass('hide');
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
                    mQuery('#file_payload_jsoneditor').removeClass('advanced');
                }
                else if (mode === 'advanced') {
                    codeStop();
                    $advancedView.addClass('btn-success');
                    $simpleView.removeClass('btn-success');
                    $codeView.removeClass('btn-success');
                    mQuery('#file_payload_jsoneditor').addClass('advanced');
                }
                else if (mode === 'code') {
                    codeStart();
                    $codeView.addClass('btn-success');
                    $simpleView.removeClass('btn-success');
                    $advancedView.removeClass('btn-success');
                    mQuery('#file_payload_jsoneditor').removeClass('advanced');
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
        var filePayloadTestCodeMirror;
        mQuery('#file_payload_test').click(function () {
            var resultContainerSelector = '#file_payload_test_result',
                $button = mQuery(this),
                $resultContainer = mQuery(resultContainerSelector),
                $result = $resultContainer.find('#file_payload_test_result_yaml'),
                $message = $resultContainer.find('#file_payload_test_result_message'),
                $error = $resultContainer.find('#file_payload_test_result_error'),
                $footer = $resultContainer.find('.modal-footer'),
                $saveButton = $footer.find('.btn-save'),
                $attributionDefault = mQuery('#contactclient_attribution_default:first'),
                $attributionSettings = mQuery('#contactclient_attribution_settings:first');
            if ($button.hasClass('active')) {
                // Test Deactivation.
            }
            else {
                // Test Activation.
                var data = {
                    action: 'plugin:mauticContactClient:getFilePayloadTest',
                    filePayload: $filePayload.val(),
                    attributionDefault: $attributionDefault.length ? $attributionDefault.val() : '',
                    attributionSettings: $attributionSettings.length ? $attributionSettings.val() : ''
                };
                $result.addClass('hide');
                $message.addClass('hide');
                $error.addClass('hide');
                $footer.addClass('hide');
                mQuery.ajax({
                    url: mauticAjaxUrl,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        if (typeof response.html !== 'undefined') {
                            $resultContainer.removeClass('hide').modal('show');

                            // sends markup through core js parsers
                            if (response.html !== '') {
                                if (!filePayloadTestCodeMirror) {
                                    setTimeout(function () {
                                        $result.removeClass('hide');
                                        filePayloadTestCodeMirror = CodeMirror($result[0], {
                                            value: response.html,
                                            mode: 'yaml',
                                            theme: 'material',
                                            gutters: [],
                                            lineNumbers: false,
                                            lineWrapping: true,
                                            readOnly: true
                                        });
                                    }, 250);
                                }
                                else {
                                    setTimeout(function () {
                                        $result.removeClass('hide');
                                        filePayloadTestCodeMirror.setValue(response.html, -1);
                                    }, 250);
                                }
                                if (response.message) {
                                    var html = response.message;
                                    if (response.valid) {
                                        $message.removeClass('text-danger').addClass('text-success');
                                        html = '<i class="fa fa-thumbs-o-up faa-bounce animated"></i> ' + html;
                                    }
                                    else {
                                        $message.addClass('text-danger').removeClass('text-success');
                                        html = '<i class="fa fa-warning faa-flash animated"></i> ' + html;
                                    }
                                    $message.html(html).removeClass('hide');
                                }
                                if (response.error !== null) {
                                    var $list = mQuery('<ul></ul>'),
                                        $item = mQuery('<li></li>'),
                                        $clone;

                                    if (typeof response.error === 'string') {
                                        response.error = [response.error];
                                    }
                                    for (var i = 0; i < response.error.length; i++) {
                                        $clone = $item;
                                        $list.append($clone.text(response.error[i]));
                                    }
                                    $error.html($list.html()).removeClass('hide');
                                }
                                if (response.valid) {
                                    if ($saveButton.length) {
                                        $saveButton.removeClass('hide');
                                    }
                                    else {
                                        // Make a new save button.
                                        $saveButton = mQuery('#contactclient_buttons_save_toolbar:first').clone();
                                        $footer.append($saveButton);
                                        $saveButton.click(function () {
                                            $resultContainer.modal('hide');
                                            mQuery('#contactclient_buttons_save_toolbar:first').trigger('click');
                                        });
                                    }
                                }
                                else {
                                    $saveButton.addClass('hide');
                                }
                                $footer.removeClass('hide');
                                Mautic.onPageLoad(resultContainerSelector, response, true);
                            }
                        }
                        if (
                            typeof response.payload !== 'undefined'
                            && response.payload.length
                        ) {
                            // response.payload is already a raw string,
                            // but it may be parsed differently, depending on
                            // browser.
                            var obj = mQuery.parseJSON(response.payload),
                                raw = JSON.stringify(obj, null, 2);
                            if (raw.length && $filePayload.val() !== raw) {
                                if (typeof setJSONEditorValue !== 'undefined') {
                                    setJSONEditorValue(raw);
                                }
                                else {
                                    $filePayload.val(raw);
                                }
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