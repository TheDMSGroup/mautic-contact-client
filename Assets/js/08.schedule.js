// Schedule - Hours of Operation.
Mautic.contactclientSchedule = function () {
    mQuery(document).ready(function () {

        var $scheduleHoursTarget = mQuery('#contactclient_schedule_hours_widget'),
            $scheduleHoursSource = mQuery('#contactclient_schedule_hours');
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
            $scheduleHoursSource.addClass('hide');
            mQuery('#contactclient_schedule_hours_widget .operationState, #contactclient_schedule_hours_widget input').change(function () {
                mQuery('#contactclient_schedule_hours').val(JSON.stringify(scheduleHours.serialize()));
            });
        }
    });
};
