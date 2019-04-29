// Limits field.
Mautic.contactclientLimits = function () {
    var $limits = mQuery('#contactclient_limits:first:not(.limits-checked)');
    if ($limits.length) {

        var limitsJSONEditor;

        // Grab the JSON Schema to begin rendering the form with JSONEditor.
        mQuery.ajax({
            dataType: 'json',
            cache: true,
            url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/limits.json',
            success: function (data) {
                var schema = data;

                // Create our widget container for the JSON Editor.
                var $limitsJSONEditor = mQuery('<div>', {
                    class: 'contactclient_jsoneditor',
                    id: 'limits_jsoneditor'
                }).insertBefore($limits);

                // Instantiate the JSON Editor based on our schema.
                limitsJSONEditor = new JSONEditor($limitsJSONEditor[0], {
                    schema: schema,
                    disable_collapse: true
                });

                $limits.change(function () {
                    // Load the initial value if applicable.
                    var raw = mQuery(this).val(),
                        obj;
                    if (raw.length) {
                        try {
                            obj = mQuery.parseJSON(raw);
                            if (typeof obj === 'object') {
                                limitsJSONEditor.setValue(obj);
                            }
                        }
                        catch (e) {
                            console.warn(e);
                        }
                    }
                }).trigger('change');

                // Persist the value to the JSON Editor.
                limitsJSONEditor.on('change', function () {
                    var obj = limitsJSONEditor.getValue();
                    if (typeof obj === 'object') {
                        var raw = JSON.stringify(obj, null, 2);
                        if (raw.length) {
                            // Set the textarea.
                            $limits.val(raw);

                            // Hide the Value when the scope is global.
                            mQuery('select[name$="[scope]"]:not(.scope-checked)').off('change').on('change', function () {
                                var $value = mQuery(this).parent().parent().parent().find('input[name$="[value]"]');
                                if (parseInt(mQuery(this).val()) === 1) {
                                    $value.addClass('hide');
                                }
                                else {
                                    $value.removeClass('hide');
                                }
                            }).addClass('scope-checked').trigger('change');
                        }
                    }
                });

                $limitsJSONEditor.show();
            }
        });
        $limits.addClass('limits-checked');
    }

    var $limitsQueueSpreadTarget = mQuery('#contactclient_limits_queue_spread:first:not(.spread-checked)');
    if ($limitsQueueSpreadTarget.length) {
        $limitsQueueSpreadTarget.addClass('spread-checked');
        $limitsQueueSpreadTarget.each(function () {
            var $slider = mQuery(this),
                min = parseInt(mQuery(this).attr('min')),
                max = parseInt(mQuery(this).attr('max')),
                step = parseInt(mQuery(this).attr('step')),
                value = parseInt(mQuery(this).val()),
                options = {
                    'min': min,
                    'max': max,
                    'value': value,
                    'step': step,
                    formatter: function (val) {
                        return val + ' day' + (val > 1 ? 's' : '') + ' forward';
                    }
                };
            new Slider($slider[0], options);
        });

        mQuery('input[name="contactclient[limits_queue]"]').change(function () {
            var val = parseInt(mQuery('input[name="contactclient[limits_queue]"]:checked:first').val());
            if (1 === val) {
                $limitsQueueSpreadTarget.parent().parent().removeClass('hide');
            }
            else {
                $limitsQueueSpreadTarget.parent().parent().addClass('hide');
            }
        }).first().parent().parent().find('label.active input:first').trigger('change');
    }
};