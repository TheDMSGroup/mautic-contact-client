Mautic.contactclientOnLoad = function () {
    mQuery(document).ready(function () {
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
        var $apiPayload = mQuery('#contactclient_api_payload_widget');
        if ($apiPayload.length) {
            var apiPayload = $apiPayload.val();
            if (apiPayload.length) {
                apiPayload = mQuery.parseJSON(apiPayload);
            }
            mQuery.ajax({
                dataType: 'json',
                cache: false,
                url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/js/api_payload.json',
                success: function (data) {
                    console.log(data);
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
                        getHeaderButtonHolder: function() {
                            var el = this.getButtonHolder();
                            el.style.marginLeft = '10px';
                            el.className = 'btn-group pull-right';
                            return el;
                        },
                        // Pull "new item" buttons to the left.
                        getButtonHolder: function () {
                            var el = document.createElement('div');
                            el.className = 'btn-group';
                            return el;
                        }
                    });
                    var editor = new JSONEditor($apiPayload[0], {
                        ajax: false,
                        schema: schema,
                        theme: 'custom',
                        iconlib: 'fontawesome4',
                        disable_edit_json: true,
                        disable_properties: true,
                        disable_array_delete_all_rows: true,
                        disable_array_delete_last_row: true
                    });
                }
            });
        }
    });
};