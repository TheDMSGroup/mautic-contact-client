Mautic.contactclientOnLoad = function () {
    // getScriptCachedOnce for faster page loads in the backend.
    mQuery.getScriptCachedOnce = function (url, callback) {
        if (
            typeof window.getScriptCachedOnce !== 'undefined'
            && window.getScriptCachedOnce.indexOf(url) !== -1
        ) {
            callback();
            return mQuery(this);
        }
        else {
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

        // @todo - Exclusivity field.

        // @todo - Filtering field.
        // var $filter = mQuery('#contactclient_filter');
        // if ($filter.length) {
        //     mQuery.getScriptCachedOnce('https://cdn.jsdelivr.net/combine/' +
        //         'npm/bootstrap-slider@10,npm/bootstrap-datepicker@1,' +
        //         'npm/jQuery-QueryBuilder@2/dist/js/query-builder.standalone.min.js',
        //         function () {
        //             var $filterQueryBuilder = mQuery('<div>', {
        //                 id: 'contactclient_filter_querybuilder'
        //             }).insertBefore($filter);
        //
        //             $filter.addClass('hide');
        //         }
        //     );
        // }

        // @todo - Limits field.

        // API Payload field.
        var $apiPayload = mQuery('#contactclient_api_payload');
        if ($apiPayload.length) {

            var apiPayloadCodeMirror,
                apiPayloadJSONEditor;

            // API Payload JSON Schema.
            mQuery.getScriptCachedOnce('https://cdn.jsdelivr.net/combine/' +
                'npm/bootstrap-slider@10,npm/bootstrap-datepicker@1,' +
                'npm/jQuery-QueryBuilder@2/dist/js/query-builder.standalone.min.js,' +
                'gh/heathdutton/json-editor@0.7.30/dist/jsoneditor.min.js',
                function () {
                    // Grab the JSON Schema to begin rendering the form with JSONEditor.
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

                            // Establish default success definition filters.
                            var successDefinitionFiltersDefault = [{
                                id: 'status',
                                label: 'Status Code',
                                type: 'string',
                                input: 'select',
                                values: {
                                    '1xx': '1xx: Informational',
                                    '100': '100: Continue',
                                    '101': '101: Switching Protocols',
                                    '2xx': '2xx: Successful',
                                    '200': '200: OK',
                                    '201': '201: Created',
                                    '202': '202: Accepted',
                                    '203': '203: Non-Authoritative Information',
                                    '204': '204: No Content',
                                    '205': '205: Reset Content',
                                    '206': '206: Partial Content',
                                    '3xx': '3xx: Redirection',
                                    '300': '300: Multiple Choices',
                                    '301': '301: Moved Permanently',
                                    '302': '302: Found',
                                    '303': '303: See Other',
                                    '304': '304: Not Modified',
                                    '305': '305: Use Proxy',
                                    '307': '307: Temporary Redirect',
                                    '4xx': '4xx: Client Error',
                                    '400': '400: Bad Request',
                                    '401': '401: Unauthorized',
                                    '402': '402: Payment Required',
                                    '403': '403: Forbidden',
                                    '404': '404: Not Found',
                                    '405': '405: Method Not Allowed',
                                    '406': '406: Not Acceptable',
                                    '407': '407: Proxy Authentication Required',
                                    '408': '408: Request Timeout',
                                    '409': '409: Conflict',
                                    '410': '410: Gone',
                                    '411': '411: Length Required',
                                    '412': '412: Precondition Failed',
                                    '413': '413: Payload Too Large',
                                    '414': '414: URI Too Long',
                                    '415': '415: Unsupported Media Type',
                                    '416': '416: Range Not Satisfiable',
                                    '417': '417: Expectation Failed',
                                    '418': '418: I\'m a teapot',
                                    '426': '426: Upgrade Required',
                                    '5xx': '5xx: Server Error',
                                    '500': '500: Internal Server Error',
                                    '501': '501: Not Implemented',
                                    '502': '502: Bad Gateway',
                                    '503': '503: Service Unavailable',
                                    '504': '504: Gateway Time-out',
                                    '505': '505: HTTP Version Not Supported',
                                    '102': '102: Processing',
                                    '207': '207: Multi-Status',
                                    '226': '226: IM Used',
                                    '308': '308: Permanent Redirect',
                                    '422': '422: Unprocessable Entity',
                                    '423': '423: Locked',
                                    '424': '424: Failed Dependency',
                                    '428': '428: Precondition Required',
                                    '429': '429: Too Many Requests',
                                    '431': '431: Request Header Fields Too Large',
                                    '451': '451: Unavailable For Legal Reasons',
                                    '506': '506: Variant Also Negotiates',
                                    '507': '507: Insufficient Storage',
                                    '511': '511: Network Authentication Required',
                                    '7xx': '7xx: Developer Error'
                                },
                                operators: ['equal', 'not_equal']
                            }, {
                                id: 'header',
                                label: 'Header Text',
                                type: 'string'
                            }, {
                                id: 'body',
                                label: 'Body Text',
                                type: 'string'
                            }];

                            // Add a "query" field type using the Query Builder.
                            JSONEditor.defaults.editors.query = JSONEditor.defaults.editors.string.extend({
                                postBuild: function () {
                                    // Default success rules (if the status code is 200).
                                    var element = this,
                                        successDefinitionRules = {
                                        condition: 'AND',
                                        rules: [{
                                            id: 'status',
                                            operator: 'equal',
                                            value: '200'
                                        }]
                                    };
                                    // Load a saved value if relevant.
                                    if (this.input.value) {
                                        if (typeof this.input.value === 'object') {
                                            successDefinitionRules = this.input.value;
                                        } else {
                                            try {
                                                var obj = mQuery.parseJSON(this.input.value);
                                            }
                                            catch (e) {
                                                console.warn('Invalid JSON in success definition');
                                            }
                                            if (typeof obj === 'object' && obj.length > 0) {
                                                successDefinitionRules = obj;
                                            }
                                        }
                                    }
                                    // Progressively Enhance the textarea into a Query Builder.
                                    mQuery('<div>', {class: 'query-builder'})
                                        .insertAfter(element.input)
                                        .queryBuilder({
                                            plugins: ['bt-tooltip-errors'],
                                            filters: successDefinitionFiltersDefault,
                                            icons: {
                                                add_group: 'fa fa-plus',
                                                add_rule: 'fa fa-plus',
                                                remove_group: 'fa fa-times',
                                                remove_rule: 'fa fa-times',
                                                sort: 'fa fa-sort',
                                                error: 'fa fa-exclamation-triangle'
                                            },
                                            rules: successDefinitionRules
                                        })
                                        // On any change to Success Definition:
                                        .on('afterDeleteGroup.queryBuilder afterUpdateRuleFilter.queryBuilder afterAddRule.queryBuilder afterDeleteRule.queryBuilder afterUpdateRuleValue.queryBuilder afterUpdateRuleOperator.queryBuilder afterUpdateGroupCondition.queryBuilder', function () {
                                            // Save the value to the hidden textarea.
                                            var rules = mQuery(this).queryBuilder('getRules', {
                                                    get_flags: true,
                                                    skip_empty: true,
                                                    allow_invalid: true
                                                });
                                            element.setValue(rules);
                                        })
                                        .parent().find('textarea').addClass('hide');
                                }
                            });

                            // Create our widget container for the JSON Editor.
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
                                disable_array_delete_last_row: true,
                                required_by_default: true
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

                            // Persist the value to the JSON Editor.
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
            // Mautic currently ships with CodeMirror 5.15.2.
            if (typeof CodeMirror !== 'undefined') {
                mQuery.getScriptCachedOnce('https://cdn.jsdelivr.net/combine/' +
                    'npm/jsonlint@1.6.2/lib/jsonlint.min.js,' +
                    'npm/codemirror@5.15.2/addon/lint/lint.js,' +
                    'npm/codemirror@5.15.2/addon/lint/json-lint.min.js,' +
                    'npm/codemirror@5.15.2/addon/edit/matchbrackets.min.js,' +
                    'npm/codemirror@5.15.2/addon/display/fullscreen.min.js',
                    function () {
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
                    }
                );
            }
        }

        // Hours of Operation.
        var $scheduleHoursTarget = mQuery('#contactclient_schedule_hours_widget');
        if ($scheduleHoursTarget.length) {
            mQuery.getScriptCachedOnce('https://cdn.jsdelivr.net/combine/' +
                'npm/timepicker@1.11.12/jquery.timepicker.min.js,' +
                'gh/gEndelf/jquery.businessHours@1.0.1/jquery.businessHours.min.js',
                function () {
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
                }
            );
        }
    });
};