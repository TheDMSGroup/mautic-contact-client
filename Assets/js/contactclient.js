Mautic.contactclientOnLoad = function () {
    // getScriptCached for faster page loads in the backend.
    mQuery.getScriptCached = function (url, callback) {
        return mQuery.ajax({
            url: url,
            dataType: 'script',
            cache: true
        }).done(callback);
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

            var apiPayloadAce,
                apiPayloadJSONEditor;

            // API Payload JSON Schema.
            mQuery.getScriptCached('https://cdn.rawgit.com/heathdutton/json-editor/v0.7.28/dist/jsoneditor.min.js', function () {
                mQuery.ajax({
                    dataType: 'json',
                    cache: false,
                    url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/js/api_payload.json',
                    success: function (data) {
                        var schema = data;
                        JSONEditor.plugins.ace.theme = 'github';
                        // Custom theme to add more indication colors.
                        JSONEditor.defaults.themes.custom = JSONEditor.defaults.themes.bootstrap3.extend({
                            getButton: function (text, icon, title) {
                                var el = this._super(text, icon, title);
                                if (title.indexOf('Delete') !== -1) {
                                    el.className = el.className.replace('btn-default', 'btn-danger');
                                }
                                else if (title.indexOf('Add') !== -1) {
                                    el.className = el.className.replace('btn-default', 'btn-success');
                                }
                                else {
                                    el.className = el.className.replace('btn-default', 'btn-primary');
                                }
                                return el;
                            },
                            // Pull header nav to the right.
                            getHeaderButtonHolder: function () {
                                var el = this.getButtonHolder();
                                el.className = 'btn-group btn-right';
                                return el;
                            },
                            // Pull "new item" buttons to the left.
                            getButtonHolder: function () {
                                var el = document.createElement('div');
                                el.className = 'btn-group btn-left';
                                return el;
                            }
                        });

                        // Create our widget container.
                        var $apiPayloadJSONEditor = mQuery('<div>', {
                                id: 'contactclient_api_payload_jsoneditor'
                            })
                            .insertBefore($apiPayload);

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

            // API Payload Raw JSON using Ace.
            mQuery.getScriptCached('https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.9/ace.js', function () {
                // Progressive enhancement of the textarea to ace.
                var $apiPayloadAce = mQuery('<div>', {
                    id: 'contactclient_api_payload_ace',
                    class: 'hide well'
                }).insertBefore($apiPayload);
                $apiPayload.css({'display': 'none'});
                apiPayloadAce = ace.edit($apiPayloadAce[0]);
                apiPayloadAce.setOptions({
                    maxLines: Infinity
                });
                apiPayloadAce.$blockScrolling = Infinity;
                apiPayloadAce.setTheme('ace/theme/github');
                apiPayloadAce.getSession().setMode('ace/mode/json');
                apiPayloadAce.getSession().setTabSize(2);
                apiPayloadAce.getSession().setUseWrapMode(true);
                apiPayloadAce.setValue($apiPayload.val(), -1);
                apiPayloadAce.on('change', function () {
                    // Set the value to the hidden textarea.
                    var raw = apiPayloadAce.getValue();
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
                            // Deactivating Ace.
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
                                    $apiPayloadAce.addClass('hide');
                                    mQuery('#contactclient_api_payload_jsoneditor').removeClass('hide');
                                }
                            }
                        }
                        else {
                            // Activating Ace.
                            // Send the value from JSONEditor to Ace.
                            if (apiPayloadAce) {
                                if (raw.length) {
                                    try {
                                        if (raw !== apiPayloadAce.getValue()) {
                                            apiPayloadAce.setValue(raw, -1);
                                        }
                                    }
                                    catch (e) {
                                        error = true;
                                        console.warn('Error setting Ace value.');
                                    }
                                }
                                if (!error) {
                                    $apiPayloadAce.removeClass('hide');
                                    mQuery('#contactclient_api_payload_jsoneditor').addClass('hide');
                                }
                            }
                        }
                    });
                mQuery('#api_payload_advanced').removeClass('hide');
            });
        }

        // Hours of Operation.
        var $scheduleHoursTarget = mQuery('#contactclient_schedule_hours_widget');
        if ($scheduleHoursTarget.length) {
            mQuery.getScriptCached('https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.10.0/jquery.timepicker.min.js', function () {
                mQuery.getScriptCached('https://cdnjs.cloudflare.com/ajax/libs/jquery.businessHours/1.0.1/jquery.businessHours.min.js', function () {
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