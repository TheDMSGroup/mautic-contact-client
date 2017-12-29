Mautic.contactclientOnLoad = function () {
    // getScriptCachedOnce for faster page loads in the backend.
    mQuery.getScriptCachedOnce = function (url, callback) {
        if (
            typeof window.getScriptCachedOnce !== 'undefined'
            && window.getScriptCachedOnce.indexOf(url) !== -1
        ) {
            callback();
            return mQuery(this);
        } else {
            return mQuery.ajax({
                url: url,
                dataType: 'script',
                cache: true
            }).done(function () {
                if (typeof window.getScriptCachedOnce === 'undefined') {
                    window.getScriptCachedOnce = [];
                }
                window.getScriptCachedOnce.push('url');
                callback();
            });
        }
    };

    mQuery(document).ready(function () {
        // Trigger payload tab visibility based on contactClient type.
        mQuery('input[name="contactclient[type]"]').change(function () {
            var val = mQuery(this).val();
            if (val === 'api') {
                mQuery('.api-payload').removeClass('hide');
                mQuery('.file-payload').addClass('hide');
                mQuery('.payload-tab').removeClass('hide');
            }
            else if (val === 'file') {
                mQuery('.api-payload').addClass('hide');
                mQuery('.file-payload').removeClass('hide');
                mQuery('.payload-tab').removeClass('hide');
            }
            else {
                mQuery('.api-payload').addClass('hide');
                mQuery('.file-payload').addClass('hide');
                mQuery('.payload-tab').addClass('hide');
            }
        }).first().parent().parent().find('label.active input:first').trigger('change');

        // Hide the right column when Payload tab is open to give more room for
        // table entry.
        var activeTab = '#details';
        mQuery('.contactclient-tab').click(function () {
            var thisTab = mQuery(this).attr('href');
            if (thisTab !== activeTab) {
                activeTab = thisTab;
                if (activeTab === '#payload') {
                    // Expanded view.
                    mQuery('.contactclient-left').addClass('col-md-12').removeClass('col-md-9');
                    mQuery('.contactclient-right').addClass('hide');
                }
                else {
                    // Standard view.
                    mQuery('.contactclient-left').removeClass('col-md-12').addClass('col-md-9');
                    mQuery('.contactclient-right').removeClass('hide');
                }
            }
        });

        // API Payload field.
        var $apiPayload = mQuery('#contactclient_api_payload');
        if ($apiPayload.length) {

            var apiPayloadCodeMirror,
                apiPayloadJSONEditor;

            // API Payload JSON Schema.
            mQuery.getScriptCachedOnce('https://cdn.rawgit.com/heathdutton/json-editor/7a16825ee4472f6e490b4ea674707e9805fa0a93/dist/jsoneditor.min.js', function () {
                mQuery.ajax({
                    dataType: 'json',
                    cache: false,
                    url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/js/api_payload.json',
                    success: function (data) {
                        var schema = data;
                        // Extend the bootstrap3 theme with our own
                        // customizations.
                        JSONEditor.defaults.themes.custom = JSONEditor.defaults.themes.bootstrap3.extend({
                            getButton: function (text, icon, title) {
                                var el = this._super(text, icon, title);
                                if (title.indexOf('Delete') !== -1) {
                                    el.className = el.className.replace('btn-default', 'btn-sm btn-xs btn-danger');
                                }
                                else if (title.indexOf('Add') !== -1) {
                                    el.className = el.className.replace('btn-default', 'btn-md btn-primary');
                                }
                                else {
                                    el.className = el.className.replace('btn-default', 'btn-sm btn-xs btn-secondary');
                                }
                                return el;
                            },
                            // Pull header nav to the right.
                            getHeaderButtonHolder: function () {
                                var el = this.getButtonHolder();
                                el.className = 'btn-group btn-group-sm btn-right';
                                return el;
                            },
                            // Pull "new item" buttons to the left.
                            getButtonHolder: function () {
                                var el = document.createElement('div');
                                el.className = 'btn-group btn-left';
                                return el;
                            },
                            // Make the h3 elements clickable.
                            getHeader: function (text) {
                                var el = document.createElement('h3');
                                el.onclick = function (e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    var $collapseButton = mQuery(this).find('> div.btn-group > button.json-editor-btn-collapse:first:visible');
                                    if ($collapseButton.length) {
                                        var el = $collapseButton[0];
                                        if (el) {
                                            var event = new MouseEvent('click', {
                                                'view': window,
                                                'bubbles': false,
                                                'cancelable': true
                                            });
                                            el.dispatchEvent(event);
                                        }
                                    }
                                };
                                el.style.cursor = 'pointer';
                                if (typeof text === 'string') {
                                    el.textContent = text;
                                }
                                else {
                                    el.appendChild(text);
                                }
                                return el;
                            }
                        });

                        // Create our widget container.
                        var $apiPayloadJSONEditor = mQuery('<div>', {
                            id: 'contactclient_api_payload_jsoneditor'
                        }).insertBefore($apiPayload);

                        // Instantiate the JSON Editor based on our schema.
                        apiPayloadJSONEditor = new JSONEditor($apiPayloadJSONEditor[0], {
                            ajax: false,
                            schema: schema,
                            theme: 'custom',
                            iconlib: 'fontawesome4',
                            disable_edit_json: true,
                            disable_properties: true,
                            disable_array_delete_all_rows: true,
                            disable_array_delete_last_row: true
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
                                console.warn('Invalid JSON');
                            }
                        }

                        // Persist the value to the field to be saved.
                        apiPayloadJSONEditor.on('change', function () {
                            var obj = apiPayloadJSONEditor.getValue();
                            if (typeof obj === 'object') {
                                var raw = JSON.stringify(obj, null, '  ');
                                if (raw.length) {
                                    // Set the textarea.
                                    $apiPayload.val(raw);
                                }
                            }
                        });
                    }
                });
            });

            // API Payload Raw JSON using CodeMirror.
            if (typeof CodeMirror !== 'undefined') {
                mQuery.getScriptCachedOnce('https://rawgit.com/heathdutton/jsonlint/master/lib/jsonlint.js', function () {
                    mQuery.getScriptCachedOnce('https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.15.2/addon/lint/lint.js', function () {
                        mQuery.getScriptCachedOnce('https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.15.2/addon/lint/json-lint.min.js', function () {
                            mQuery.getScriptCachedOnce('https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.15.2/addon/edit/matchbrackets.min.js', function () {
                                mQuery.getScriptCachedOnce('https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.15.2/addon/display/fullscreen.min.js', function () {
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
                                                            console.warn('Invalid JSON');
                                                        }
                                                    }
                                                    if (!error) {
                                                        $apiPayloadCodeMirror.addClass('hide');
                                                        mQuery('#contactclient_api_payload_jsoneditor').removeClass('hide');
                                                    }
                                                    else {
                                                        mQuery(this).toggleClass('active');
                                                    }
                                                }
                                            }
                                            else {
                                                // Activating CodeMirror.
                                                // Send the value from
                                                // JSONEditor to CodeMirror.
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
                                                        mQuery('#contactclient_api_payload_jsoneditor').addClass('hide');
                                                        apiPayloadCodeMirror.refresh();
                                                    }
                                                    else {
                                                        mQuery(this).toggleClass('active');
                                                    }
                                                }
                                            }
                                        });
                                    mQuery('#api_payload_advanced').removeClass('hide');
                                });
                            });
                        });
                    });
                });
            }
        }

        // Hours of Operation.
        var $scheduleHoursTarget = mQuery('#contactclient_schedule_hours_widget');
        if ($scheduleHoursTarget.length) {
            mQuery.getScriptCachedOnce('https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.10.0/jquery.timepicker.min.js', function () {
                mQuery.getScriptCachedOnce('https://cdnjs.cloudflare.com/ajax/libs/jquery.businessHours/1.0.1/jquery.businessHours.min.js', function () {
                    var operationTime = mQuery('#contactclient_schedule_hours').val();
                    if (operationTime.length) {
                        try {
                            operationTime = mQuery.parseJSON(operationTime);
                        }
                        catch (e) {
                            console.warn('Invalid JSON');
                        }
                    }
                    // @todo - More sane defaults.
                    if (typeof operationTime !== 'object') {
                        operationTime = [
                            {},
                            {},
                            {},
                            {},
                            {},
                            {isActive: false},
                            {isActive: false}
                        ];
                    }
                    var scheduleHours = $scheduleHoursTarget.businessHours({
                        operationTime: operationTime,
                        checkedColorClass: 'btn-success',
                        uncheckedColorClass: 'btn-danger',
                        postInit: function () {
                            mQuery('.operationTimeFrom, .operationTimeTill').timepicker({
                                'timeFormat': 'H:i',
                                'step': 15
                            });
                        },
                        dayTmpl: '<div class="dayContainer">' +
                        '<div data-original-title="" class="colorBox"><input type="checkbox" class="invisible operationState"></div>' +
                        '<div class="weekday"></div>' +
                        '<div class="operationDayTimeContainer">' +
                        '<div class="operationTime input-group"><span class="input-group-addon"><i class="fa fa-sun-o"></i></span><input type="text" name="startTime" class="mini-time form-control operationTimeFrom" value=""></div>' +
                        '<div class="operationTime input-group"><span class="input-group-addon"><i class="fa fa-moon-o"></i></span><input type="text" name="endTime" class="mini-time form-control operationTimeTill" value=""></div>' +
                        '</div></div>'
                    });
                    mQuery('#contactclient_schedule_hours_widget .operationState, #contactclient_schedule_hours_widget input').change(function () {
                        mQuery('#contactclient_schedule_hours').val(JSON.stringify(scheduleHours.serialize()));
                    });
                });
            });
        }
    });
};