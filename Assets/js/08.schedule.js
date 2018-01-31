// Schedule - Hours of Operation.
Mautic.contactclientSchedule = function () {
    mQuery(document).ready(function () {

        var $scheduleHoursTarget = mQuery('#contactclient_schedule_hours_widget:first'),
            $scheduleHoursSource = mQuery('#contactclient_schedule_hours:first');
        if ($scheduleHoursTarget.length && $scheduleHoursSource.length) {
            var operationTime = $scheduleHoursSource.val();
            if (operationTime.length) {
                try {
                    operationTime = mQuery.parseJSON(operationTime);
                }
                catch (e) {
                    console.warn('Invalid JSON');
                }
            }
            if (typeof operationTime !== 'object') {
                operationTime = [
                    {},
                    {},
                    {},
                    {},
                    {},
                    {},
                    {}
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
            $scheduleHoursSource.addClass('hide');
            mQuery('#contactclient_schedule_hours_widget .operationState, #contactclient_schedule_hours_widget input').change(function () {
                mQuery('#contactclient_schedule_hours').val(JSON.stringify(scheduleHours.serialize()));
            });

        }

        var $exclusions = mQuery('#contactclient_schedule_exclusions:first');
        if ($exclusions.length) {
            var exclusionsJSONEditor;

            // Grab the JSON Schema to begin rendering the form with
            // JSONEditor.
            mQuery.ajax({
                dataType: 'json',
                cache: true,
                url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/exclusions.json',
                success: function (data) {
                    var schema = data;

                    // Create our widget container for the JSON Editor.
                    var $exclusionsJSONEditor = mQuery('<div>', {
                        class: 'contactclient_jsoneditor'
                    }).insertBefore($exclusions);

                    // Instantiate the JSON Editor based on our schema.
                    exclusionsJSONEditor = new JSONEditor($exclusionsJSONEditor[0], {
                        schema: schema,
                        disable_collapse: true
                    });

                    $exclusions.change(function () {
                        // Load the initial value if applicable.
                        var raw = mQuery(this).val(),
                            obj;
                        if (raw.length) {
                            try {
                                obj = mQuery.parseJSON(raw);
                                if (typeof obj === 'object') {
                                    exclusionsJSONEditor.setValue(obj);
                                }
                            }
                            catch (e) {
                                console.warn(e);
                            }
                        }
                    }).trigger('change');

                    // Persist the value to the JSON Editor.
                    exclusionsJSONEditor.on('change', function () {
                        var obj = exclusionsJSONEditor.getValue();
                        if (typeof obj === 'object') {
                            var raw = JSON.stringify(obj, null, '  ');
                            if (raw.length) {
                                // Set the textarea.
                                $exclusions.val(raw);
                            }
                        }
                    });

                    $exclusions.addClass('hide');
                    $exclusionsJSONEditor.show();
                }
            });
        }
    });
};
