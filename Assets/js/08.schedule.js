// Schedule - Hours of Operation.
Mautic.contactclientSchedule = function () {

    var $scheduleHoursTarget = mQuery('#contactclient_schedule_hours_widget:first'),
        $scheduleHoursSource = mQuery('#contactclient_schedule_hours:first:not(.schedule-checked)');
    if ($scheduleHoursTarget.length && $scheduleHoursSource.length) {
        $scheduleHoursSource.addClass('schedule-checked');

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
                {
                    isActive: true,
                    timeFrom: '00:00',
                    timeTill: '23:59'
                },
                {
                    isActive: true,
                    timeFrom: '00:00',
                    timeTill: '23:59'
                },
                {
                    isActive: true,
                    timeFrom: '00:00',
                    timeTill: '23:59'
                },
                {
                    isActive: true,
                    timeFrom: '00:00',
                    timeTill: '23:59'
                },
                {
                    isActive: true,
                    timeFrom: '00:00',
                    timeTill: '23:59'
                },
                {
                    isActive: true,
                    timeFrom: '00:00',
                    timeTill: '23:59'
                },
                {
                    isActive: true,
                    timeFrom: '00:00',
                    timeTill: '23:59'
                },
                {
                    isActive: true,
                    timeFrom: '00:00',
                    timeTill: '23:59'
                }
            ];
        }
        var scheduleHours = $scheduleHoursTarget.businessHours({
            operationTime: operationTime,
            checkedColorClass: 'btn-success',
            uncheckedColorClass: 'btn-danger',
            postInit: function () {
                mQuery('.operationTimeFrom, .operationTimeTill').timepicker({
                    timeFormat: 'H:i',
                    step: function (i) {
                        return (i < 48) ? 30 : 29;
                    }
                });
            },
            dayTmpl: '<div class="dayContainer">' +
                '<div class="weekday"></div>' +
                '<div data-original-title="" style="padding:2px; width: 103%; height:auto;" class="colorBox schedule-toggle">' +
                    '<input type="checkbox" class="invisible operationState">' +
                    '<div class="operationDayTimeContainer">' +
                        '<div class="operationTime input-group"><span class="input-group-addon"><i class="fa fa-sun-o"></i></span><input type="text" name="startTime" class="mini-time form-control operationTimeFrom" value=""></div>' +
                        '<div class="operationTime input-group"><span class="input-group-addon"><i class="fa fa-moon-o"></i></span><input type="text" name="endTime" class="mini-time form-control operationTimeTill" value=""></div>' +
                    '</div>' +
                '</div>' +
            '</div>'
        });
        mQuery('#contactclient_schedule_hours_widget .operationState, #contactclient_schedule_hours_widget input').change(function () {
            mQuery('#contactclient_schedule_hours').val(JSON.stringify(scheduleHours.serialize(), null, 2));
        });
        $scheduleHoursTarget.find('div.input-group').click(function(event) {
            event.stopPropagation();
            return false;
        });
    }

    var $exclusions = mQuery('#contactclient_schedule_exclusions:first:not(.exclusions-checked)');
    if ($exclusions.length) {
        $exclusions.addClass('exclusions-checked');
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
                        var raw = JSON.stringify(obj, null, 2);
                        if (raw.length) {
                            // Set the textarea.
                            $exclusions.val(raw);
                        }
                    }
                });

                $exclusionsJSONEditor.show();
            }
        });
    }
};
