// Limits field.
Mautic.contactclientLimits = function () {
    var $limits = mQuery('#contactclient_limits:not(.hide):first');
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

                $limits.addClass('hide');
                $limitsJSONEditor.show();
            }
        });

    }
};