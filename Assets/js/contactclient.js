var APISchema = {
    'definitions': {},
    'title': 'API Payload',
    '$schema': 'http://json-schema.org/draft-04/schema#',
    'id': 'http://example.com/example.json',
    'type': 'object',
    'options': {
        'disable_collapse': true
    },
    'properties': {
        'operations': {
            'title': 'Operations',
            'id': '/properties/operations',
            'type': 'array',
            'options': {
                'collapsed': false,
                'disable_collapse': true
            },
            'items': {
                'id': '/properties/operations/items',
                'title': 'Operation',
                'type': 'object',
                'properties': {
                    'name': {
                        'id': '/properties/operations/items/properties/name',
                        'propertyOrder': 10,
                        'type': 'string',
                        'title': 'Name',
                        'description': 'The name of this particular API operation.',
                        'default': 'Send'
                    },
                    'description': {
                        'id': '/properties/operations/items/properties/description',
                        'propertyOrder': 20,
                        'type': 'string',
                        'title': 'Description',
                        'description': 'Optional description of this API operation.',
                        'format': 'html',
                        'options': {
                            'wysiwyg': true
                        },
                        'default': ''
                    },
                    'method': {
                        'id': '/properties/operations/items/properties/method',
                        'propertyOrder': 30,
                        'type': 'string',
                        'title': 'Method',
                        'description': 'The method used for this API operation.',
                        'default': 'post',
                        'enumSource': [{
                            'source': [
                                {
                                    'value': 'get',
                                    'title': 'GET'
                                },
                                {
                                    'value': 'post',
                                    'title': 'POST'
                                },
                                {
                                    'value': 'put',
                                    'title': 'PUT'
                                },
                                {
                                    'value': 'delete',
                                    'title': 'DELETE'
                                },
                                {
                                    'value': 'patch',
                                    'title': 'PATCH'
                                }
                            ],
                            'title': '{{item.title}}',
                            'value': '{{item.value}}'
                        }]
                    },
                    'url': {
                        'id': '/properties/operations/items/properties/url',
                        'propertyOrder': 40,
                        'type': 'string',
                        'title': 'URL',
                        'description': 'The complete web address we will be communicating with.',
                        'default': '',
                        'required': true
                    },
                    'test_url': {
                        'id': '/properties/operations/items/properties/test_url',
                        'propertyOrder': 50,
                        'type': 'string',
                        'title': 'Test URL',
                        'description': 'Optionally specify an alternative URL to communicate with during tests, commonly used for staging/dev environments.',
                        'default': ''
                    },
                    'request_format': {
                        'id': '/properties/operations/items/properties/request_format',
                        'propertyOrder': 60,
                        'type': 'string',
                        'title': 'Request Format',
                        'description': 'The method used to format fields to be sent to this endpoint.',
                        'default': 'form',
                        'enumSource': [{
                            'source': [
                                {
                                    'value': 'text',
                                    'title': 'Raw text'
                                },
                                {
                                    'value': 'json',
                                    'title': 'JSON'
                                },
                                {
                                    'value': 'xml',
                                    'title': 'XML'
                                },
                                {
                                    'value': 'form',
                                    'title': 'Form'
                                }
                            ],
                            'title': '{{item.title}}',
                            'value': '{{item.value}}'
                        }]
                    },
                    'request_headers': {
                        'id': '/properties/operations/items/properties/request_headers',
                        'propertyOrder': 70,
                        'title': 'Request Headers',
                        'type': 'array',
                        'items': {
                            'id': '/properties/operations/items/properties/request_headers/items',
                            'title': 'Header',
                            'type': 'object',
                            'properties': {
                                'key': {
                                    'id': '/properties/operations/items/properties/request_headers/items/properties/key',
                                    'propertyOrder': 10,
                                    'type': 'string',
                                    'title': 'Key',
                                    'description': 'The name of the field being sent.',
                                    'default': '',
                                    'minLength': 0,
                                    'maxLength': 255
                                },
                                'value': {
                                    'id': '/properties/operations/items/properties/request_headers/items/properties/value',
                                    'propertyOrder': 20,
                                    'type': 'string',
                                    'title': 'Value',
                                    'description': 'The value of the field being sent.',
                                    'default': '',
                                    'minLength': 0,
                                    'maxLength': 255
                                },
                                'default_value': {
                                    'id': '/properties/operations/items/properties/request_headers/items/properties/default_value',
                                    'propertyOrder': 30,
                                    'type': 'string',
                                    'title': 'Default Value',
                                    'description': 'An optional value that will be used if the value field renders as blank.',
                                    'default': '',
                                    'minLength': 0,
                                    'maxLength': 255
                                },
                                'test_value': {
                                    'id': '/properties/operations/items/properties/request_headers/items/properties/test_value',
                                    'propertyOrder': 40,
                                    'type': 'string',
                                    'title': 'Test Value',
                                    'description': 'An optional value to override other values during test requests.',
                                    'default': '',
                                    'minLength': 0,
                                    'maxLength': 255
                                },
                                'test_only': {
                                    'id': '/properties/operations/items/properties/request_headers/items/properties/test_only',
                                    'propertyOrder': 50,
                                    'type': 'boolean',
                                    'format': 'checkbox',
                                    'title': 'Test Only',
                                    'description': 'Set to true to only send this field when running a test.',
                                    'default': false
                                },
                                'description': {
                                    'id': '/properties/operations/items/properties/request_headers/items/properties/description',
                                    'propertyOrder': 60,
                                    'type': 'string',
                                    'title': 'Description',
                                    'description': '',
                                    'default': '',
                                    'minLength': 0,
                                    'maxLength': 255
                                },
                                'required': {
                                    'id': '/properties/operations/items/properties/request_headers/items/properties/required',
                                    'propertyOrder': 70,
                                    'type': 'boolean',
                                    'format': 'checkbox',
                                    'title': 'Required',
                                    'description': 'Set to true to prevent sending contacts to this client if this field is empty.',
                                    'default': false
                                }
                            }
                        }
                    },
                    'request_body': {
                        'id': '/properties/operations/items/properties/request_body',
                        'propertyOrder': 80,
                        'title': 'Request Body',
                        'type': 'array',
                        'items': {
                            'id': '/properties/operations/items/properties/request_body/items',
                            'type': 'object',
                            'properties': {}
                        }
                    },
                    'response_format': {
                        'id': '/properties/operations/items/properties/response_format',
                        'propertyOrder': 90,
                        'title': 'Response Format',
                        'type': 'string',
                        'description': 'The method used to format fields that we will be receiving.',
                        'default': 'json',
                        'enumSource': [{
                            'source': [
                                {
                                    'value': 'text',
                                    'title': 'Raw text'
                                },
                                {
                                    'value': 'json',
                                    'title': 'JSON'
                                },
                                {
                                    'value': 'xml',
                                    'title': 'XML'
                                },
                                {
                                    'value': 'form',
                                    'title': 'Form'
                                }
                            ],
                            'title': '{{item.title}}',
                            'value': '{{item.value}}'
                        }]
                    },
                    'response_headers': {
                        'id': '/properties/operations/items/properties/response_headers',
                        'propertyOrder': 100,
                        'title': 'Response Headers',
                        'type': 'array',
                        'items': {
                            'id': '/properties/operations/items/properties/response_headers/items',
                            'type': 'object',
                            'properties': {}
                        }
                    },
                    'response_body': {
                        'id': '/properties/operations/items/properties/response_body',
                        'propertyOrder': 110,
                        'title': 'Response Body',
                        'type': 'array',
                        'items': {
                            'id': '/properties/operations/items/properties/response_body/items',
                            'type': 'object',
                            'properties': {}
                        }
                    },
                    'success': {
                        'id': '/properties/operations/items/properties/success',
                        'propertyOrder': 120,
                        'title': 'Success Definition',
                        'type': 'array',
                        'items': {
                            'id': '/properties/operations/items/properties/success/items',
                            'type': 'object',
                            'properties': {
                                'id': 'grizzlybear',
                                'title': 'Empty Object',
                                'description': 'This accepts anything, as long as it\'s valid JSON.'
                            }
                        }
                    }
                }
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
                schema: APISchema,
                theme: 'bootstrap3',
                iconlib: 'fontawesome4',
                disable_edit_json: true,
                disable_properties: true,
                disable_array_delete_all_rows: true,
                disable_array_delete_last_row: true
            });
        }
        // mQuery('#contactclient_api_payload').val()

        //
        // var editor = new JSONEditor(element, options);
    });
};
