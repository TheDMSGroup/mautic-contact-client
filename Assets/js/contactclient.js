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

        // Filter field.
        // var $filter = mQuery('#contactclient_filter');
        // if ($filter.length) {
        //     mQuery.getScriptCachedOnce('https://cdn.jsdelivr.net/combine/' +
        //         'npm/bootstrap-slider@10,npm/bootstrap-datepicker@1,' +
        //         'npm/selectize@0.12.4/dist/js/standalone/selectize.min.js,' +
        //         'npm/jQuery-QueryBuilder@2/dist/js/query-builder.standalone.min.js',
        //         function () {
        //             var $filterQueryBuilder = mQuery('<div>', {
        //                 id: 'contactclient_filter_querybuilder'
        //             }).insertBefore($filter);
        //
        //             var rules_widgets = {
        //                 condition: 'OR',
        //                 rules: [{
        //                     id: 'date',
        //                     operator: 'equal',
        //                     value: '1991/11/17'
        //                 }, {
        //                     id: 'rate',
        //                     operator: 'equal',
        //                     value: 22
        //                 }, {
        //                     id: 'category',
        //                     operator: 'equal',
        //                     value: '38'
        //                 }, {
        //                     condition: 'AND',
        //                     rules: [{
        //                         id: 'coord',
        //                         operator: 'equal',
        //                         value: 'B.3'
        //                     }]
        //                 }]
        //             };
        //             // Fix for Selectize
        //             $filterQueryBuilder.on('afterCreateRuleInput.queryBuilder', function (e, rule) {
        //                 if (rule.filter.plugin === 'selectize') {
        //                     rule.$el.find('.rule-value-container').css('min-width', '200px')
        //                         .find('.selectize-control').removeClass('form-control');
        //                 }
        //             }).queryBuilder({
        //                 plugins: ['bt-tooltip-errors'],
        //
        //                 filters: [{
        //                     id: 'date',
        //                     label: 'Datepicker',
        //                     type: 'date',
        //                     validation: {
        //                         format: 'YYYY/MM/DD'
        //                     },
        //                     plugin: 'datepicker',
        //                     plugin_config: {
        //                         format: 'yyyy/mm/dd',
        //                         todayBtn: 'linked',
        //                         todayHighlight: true,
        //                         autoclose: true
        //                     }
        //                 }, {
        //                     id: 'rate',
        //                     label: 'Slider',
        //                     type: 'integer',
        //                     validation: {
        //                         min: 0,
        //                         max: 100
        //                     },
        //                     plugin: 'slider',
        //                     plugin_config: {
        //                         min: 0,
        //                         max: 100,
        //                         value: 0
        //                     },
        //                     valueSetter: function (rule, value) {
        //                         if (rule.operator.nb_inputs === 1) {
        //                             value = [value];
        //                         }
        //                         rule.$el.find('.rule-value-container input').each(function (i) {
        //                             mQuery(this).slider('setValue', value[i] || 0);
        //                         });
        //                     },
        //                     valueGetter: function (rule) {
        //                         var value = [];
        //                         rule.$el.find('.rule-value-container input').each(function () {
        //                             value.push(mQuery(this).slider('getValue'));
        //                         });
        //                         return rule.operator.nb_inputs === 1 ? value[0] : value;
        //                     }
        //                 }, {
        //                     id: 'category',
        //                     label: 'Selectize',
        //                     type: 'string',
        //                     plugin: 'selectize',
        //                     plugin_config: {
        //                         valueField: 'id',
        //                         labelField: 'name',
        //                         searchField: 'name',
        //                         sortField: 'name',
        //                         create: true,
        //                         maxItems: 1,
        //                         plugins: ['remove_button'],
        //                         onInitialize: function () {
        //                             var that = this;
        //
        //                             if (localStorage.demoData === undefined) {
        //                                 // mQuery.getJSON(baseurl +
        //                                 // '/assets/demo-data.json', function
        //                                 // (data) { localStorage.demoData =
        //                                 // JSON.stringify(data);
        //                                 // data.forEach(function (item) {
        //                                 // that.addOption(item); }); });
        //                             }
        //                             else {
        //                                 JSON.parse(localStorage.demoData).forEach(function (item) {
        //                                     that.addOption(item);
        //                                 });
        //                             }
        //                         }
        //                     },
        //                     valueSetter: function (rule, value) {
        //                         rule.$el.find('.rule-value-container input')[0].selectize.setValue(value);
        //                     }
        //                 }, {
        //                     id: 'coord',
        //                     label: 'Coordinates',
        //                     type: 'string',
        //                     validation: {
        //                         format: /^[A-C]{1}.[1-6]{1}$/
        //                     },
        //                     input: function (rule, name) {
        //                         var $container = rule.$el.find('.rule-value-container');
        //
        //                         $container.on('change', '[name=' + name + '_1]', function () {
        //                             var h = '';
        //
        //                             switch (mQuery(this).val()) {
        //                                 case 'A':
        //                                     h = '<option value="-1">-</option> <option value="1">1</option> <option value="2">2</option>';
        //                                     break;
        //                                 case 'B':
        //                                     h = '<option value="-1">-</option> <option value="3">3</option> <option value="4">4</option>';
        //                                     break;
        //                                 case 'C':
        //                                     h = '<option value="-1">-</option> <option value="5">5</option> <option value="6">6</option>';
        //                                     break;
        //                             }
        //
        //                             $container.find('[name$=_2]')
        //                                 .html(h).toggle(!!h)
        //                                 .val('-1').trigger('change');
        //                         });
        //
        //                         return '\
        //                       <select name="' + name + '_1"> \
        //                         <option value="-1">-</option> \
        //                         <option value="A">A</option> \
        //                         <option value="B">B</option> \
        //                         <option value="C">C</option> \
        //                       </select> \
        //                       <select name="' + name + '_2" style="display:none;"></select>';
        //                     },
        //                     valueGetter: function (rule) {
        //                         return rule.$el.find('.rule-value-container [name$=_1]').val()
        //                             + '.' + rule.$el.find('.rule-value-container [name$=_2]').val();
        //                     },
        //                     valueSetter: function (rule, value) {
        //                         if (rule.operator.nb_inputs > 0) {
        //                             var val = value.split('.');
        //
        //                             rule.$el.find('.rule-value-container [name$=_1]').val(val[0]).trigger('change');
        //                             rule.$el.find('.rule-value-container [name$=_2]').val(val[1]).trigger('change');
        //                         }
        //                     }
        //                 }],
        //                 rules: rules_widgets
        //             }).on('change', function () {
        //                 var obj = mQuery(this).queryBuilder('getRules');
        //                 if (obj) {
        //                     var raw = JSON.stringify(obj);
        //                     if (raw.length) {
        //                         $filter.val(raw);
        //                     }
        //                 }
        //             });
        //         }
        //     );
        //     $filter.addClass('hide');
        // }

        // API Payload field.
        var $apiPayload = mQuery('#contactclient_api_payload');
        if ($apiPayload.length) {

            var apiPayloadCodeMirror,
                apiPayloadJSONEditor;

            // API Payload JSON Schema.
            mQuery.getScriptCachedOnce('https://cdn.jsdelivr.net/combine/' +
                'npm/bootstrap-slider@10,npm/bootstrap-datepicker@1,' +
                'npm/selectize@0.12.4/dist/js/standalone/selectize.min.js,' +
                'npm/jQuery-QueryBuilder@2/dist/js/query-builder.standalone.min.js,' +
                'gh/heathdutton/json-editor@0.7.30/dist/jsoneditor.min.js',
                function () {

                    JSONEditor.defaults.editors.query = JSONEditor.defaults.editors.string.extend({
                        // getValue: function() {
                        //     // Convert to object for better JSON, or leave as string for better failover?
                        //     return this.value;
                        // },
                        postBuild: function () {
                            this.container.className += ' query-builder';
                            console.log(this);
                            console.log('postbuild');
                            var rules = {
                                condition: 'AND',
                                rules: [{
                                    id: 'price',
                                    operator: 'less',
                                    value: 10.25
                                }, {
                                    condition: 'OR',
                                    rules: [{
                                        id: 'category',
                                        operator: 'equal',
                                        value: 2
                                    }, {
                                        id: 'category',
                                        operator: 'equal',
                                        value: 1
                                    }]
                                }]
                            };
                            var $builder = mQuery('<div>', {class: 'query-builder'})
                                .insertAfter(this.input);

                            $builder
                                .on('afterCreateRuleInput.queryBuilder', function (e, rule) {
                                    if (rule.filter.plugin === 'selectize') {
                                        rule.$el.find('.rule-value-container').css('min-width', '200px')
                                            .find('.selectize-control').removeClass('form-control');
                                    }
                                })
                                .queryBuilder({
                                    plugins: ['bt-tooltip-errors'],
                                    filters: [{
                                        id: 'name',
                                        label: 'Name',
                                        type: 'string'
                                    }, {
                                        id: 'category',
                                        label: 'Category',
                                        type: 'integer',
                                        input: 'select',
                                        values: {
                                            1: 'Books',
                                            2: 'Movies',
                                            3: 'Music',
                                            4: 'Tools',
                                            5: 'Goodies',
                                            6: 'Clothes'
                                        },
                                        operators: ['equal', 'not_equal', 'in', 'not_in', 'is_null', 'is_not_null']
                                    }, {
                                        id: 'in_stock',
                                        label: 'In stock',
                                        type: 'integer',
                                        input: 'radio',
                                        values: {
                                            1: 'Yes',
                                            0: 'No'
                                        },
                                        operators: ['equal']
                                    }, {
                                        id: 'price',
                                        label: 'Price',
                                        type: 'double',
                                        validation: {
                                            min: 0,
                                            step: 0.01
                                        }
                                    }, {
                                        id: 'id',
                                        label: 'Identifier',
                                        type: 'string',
                                        placeholder: '____-____-____',
                                        operators: ['equal', 'not_equal'],
                                        validation: {
                                            format: /^.{4}-.{4}-.{4}$/
                                        }
                                    }],
                                    icons: {
                                        add_group: 'fa fa-small fa-plus',
                                        add_rule: 'fa fa-plus',
                                        remove_group: 'fa fa-times',
                                        remove_rule: 'fa fa-times',
                                        sort: 'fa fa-sort'
                                    },
                                    rules: rules
                                });
                        }

                    });

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