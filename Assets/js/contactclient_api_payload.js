// API Payload field.
// API Payload JSON Schema.
mQuery(document).ready(function () {

    var $apiPayload = mQuery('#contactclient_api_payload');
    if ($apiPayload.length) {

        var apiPayloadCodeMirror,
            apiPayloadJSONEditor;

        // Grab the JSON Schema to begin rendering the form with
        // JSONEditor.
        mQuery.ajax({
            dataType: 'json',
            cache: false,
            url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/contactclient_api_payload.json',
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

                // Establish default success definition settings.
                // Note: Some operators are not going to be directly
                // useful.
                var defaultOperators = [
                        'equal',
                        'not_equal',
                        // 'in',
                        // 'not_in',
                        'less',
                        'less_or_equal',
                        'greater',
                        'greater_or_equal',
                        // 'between',
                        // 'not_between',
                        'begins_with',
                        'not_begins_with',
                        'contains',
                        'not_contains',
                        'ends_with',
                        'not_ends_with',
                        'is_empty',
                        'is_not_empty'
                        // 'is_null'
                    ],
                    successDefinitionFiltersDefault = [{
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
                        id: 'headersRaw',
                        label: 'Header Text (raw)',
                        type: 'string',
                        operators: defaultOperators
                    }, {
                        id: 'bodyRaw',
                        label: 'Body Text (raw)',
                        type: 'string',
                        operators: defaultOperators
                    }, {
                        id: 'bodySize',
                        label: 'Body Size',
                        type: 'integer',
                        operators: [
                            'equal',
                            'not_equal',
                            'less',
                            'less_or_equal',
                            'greater',
                            'greater_or_equal'
                        ]
                    }];

                // Add a "query" field type using the Query Builder.
                JSONEditor.defaults.editors.query = JSONEditor.defaults.editors.string.extend({
                    postBuild: function () {
                        // Default success rules (if the status
                        // code is 200).
                        var element = this,
                            successDefinitionRules = {
                                condition: 'AND',
                                rules: [{
                                    id: 'status',
                                    operator: 'equal',
                                    value: '200'
                                }]
                            };

                        // @todo - get the index of the operation.

                        // Load a saved value if relevant.
                        if (this.input.value) {
                            if (typeof this.input.value === 'object') {
                                successDefinitionRules = this.input.value;
                            }
                            else {
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
                        // Progressively Enhance the textarea into
                        // a Query Builder.
                        mQuery('<div>', {
                            id: 'success-definition-' + this.parent.parent.parent.key,
                            class: 'query-builder'
                        }).insertAfter(element.input)
                            .queryBuilder({
                                plugins: {
                                    'sortable': {
                                        icon: 'fa fa-sort'
                                    },
                                    'bt-tooltip-errors': null
                                },
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
                                // Save the value to the hidden
                                // textarea.
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
                                            // Header.
                                            if (typeof obj.operations[i].response.headers[j].key !== 'undefined' && obj.operations[i].response.headers[j].key.length) {
                                                additionalFilters.push({
                                                    id: 'headers.' + obj.operations[i].response.headers[j].key,
                                                    label: 'Header Field: ' + obj.operations[i].response.headers[j].key,
                                                    type: 'string',
                                                    operators: defaultOperators
                                                });
                                            }
                                        }
                                    }

                                    // If there is a body array...
                                    if (typeof obj.operations[i].response.body === 'object') {
                                        for (var k = 0, lenk = obj.operations[k].response.body.length; k < lenk; k++) {
                                            // Grab the keys from each
                                            // Header.
                                            if (typeof obj.operations[i].response.body[k].key !== 'undefined' && obj.operations[i].response.body[k].key.length) {
                                                additionalFilters.push({
                                                    id: 'body.' + obj.operations[i].response.body[k].key,
                                                    label: 'Body Field: ' + obj.operations[i].response.body[k].key,
                                                    type: 'string',
                                                    operators: defaultOperators
                                                });
                                            }
                                        }
                                    }

                                    // If filters were found update the
                                    // query builder.
                                    if (additionalFilters.length) {
                                        var $queryBuilder = mQuery('#success-definition-' + i);
                                        if ($queryBuilder.length) {
                                            $queryBuilder.queryBuilder('setFilters', true, successDefinitionFiltersDefault.concat(additionalFilters));
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
            })
            // Since it's functional now, unhide the widget.
            .parent().parent().removeClass('hide');
    }
});