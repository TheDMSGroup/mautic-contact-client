var guzzleSchema = {
    'operations': {
        'description': 'Operations of the web service',
        'type': 'object',
        'properties': {
            'extends': {
                'type': 'string',
                'description': 'Extend from another operation by name. The parent operation must be defined before the child.'
            },
            'httpMethod': {
                'type': 'string',
                'description': 'HTTP method used with the operation (e.g. GET, POST, PUT, DELETE, PATCH, etc)'
            },
            'uri': {
                'type': 'string',
                'description': 'URI of the operation. The uri attribute can contain URI templates. The variables of the URI template are parameters of the operation with a location value of uri'
            },
            'summary': {
                'type': 'string',
                'description': 'Short summary of what the operation does'
            },
            'class': {
                'type': 'string',
                'description': 'Custom class to instantiate instead of the default Guzzle\\Service\\Command\\OperationCommand'
            },
            'responseClass': {
                'type': 'string',
                'description': 'This is what is returned from the method. Can be a primitive, class name, or model name.'
            },
            'responseNotes': {
                'type': 'string',
                'description': 'A description of the response returned by the operation'
            },
            'responseType': {
                'type': 'string',
                'description': 'The type of response that the operation creates. If not specified, this value will be automatically inferred based on whether or not there is a model matching the name, if a matching class name is found, or set to \'primitive\' by default.',
                'enum': ['primitive', 'class', 'model', 'documentation']
            },
            'deprecated': {
                'type': 'boolean',
                'description': 'Whether or not the operation is deprecated'
            },
            'errorResponses': {
                'description': 'Errors that could occur while executing the operation',
                'type': 'array',
                'items': {
                    'type': 'object',
                    'properties': {
                        'code': {
                            'type': 'number',
                            'description': 'HTTP response status code of the error'
                        },
                        'reason': {
                            'type': 'string',
                            'description': 'Response reason phrase or description of the error'
                        },
                        'class': {
                            'type': 'string',
                            'description': 'A custom exception class that would be thrown if the error is encountered'
                        }
                    }
                }
            },
            'data': {
                'type': 'object',
                'additionalProperties': 'true'
            },
            'parameters': {
                // '$ref': 'parameters',
                'description': 'Parameters of the operation. Parameters are used to define how input data is serialized into a HTTP request.'
            },
            'additionalParameters': {
                // '$ref': 'parameters',
                'description': 'Validation and serialization rules for any parameter supplied to the operation that was not explicitly defined.'
            }
        }
    }
};

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
            // JSONEditor.plugins.epiceditor.basePath = 'json';
            JSONEditor.plugins.ace.theme = 'github';
            var editor = new JSONEditor($apiPayload[0], {
                schema: guzzleSchema,
                theme: 'bootstrap3',
                iconlib: 'fontawesome4'
            });
        }
        // mQuery('#contactclient_api_payload').val()

        //
        // var editor = new JSONEditor(element, options);
    });
};
