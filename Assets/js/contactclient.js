Mautic.contactclientOnLoad = function () {
    // Trigger tab visibility based on contactClient type.
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

    // Hours of Operation
    var $scheduleHoursTarget = mQuery('#contactclient_schedule_hours_widget');
    if ($scheduleHoursTarget.length) {
        var operationTime = mQuery('#contactclient_schedule_hours').val();
        if (operationTime.length) {
            operationTime = mQuery.parseJSON(operationTime);
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

    // API Payload.
    // mQuery('input[name="contactclient[api_payload]"]').json
};
