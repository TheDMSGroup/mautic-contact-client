// Schedule - Hours of Operation.
Mautic.contactclientRevenue = function () {
    mQuery(document).ready(function () {

        var $revenue = mQuery('#contactclient_revenueSettings:first');
        if ($revenue.length) {

            var revenueJSONEditor;

            // Grab the JSON Schema to begin rendering the form with JSONEditor.
            mQuery.ajax({
                dataType: 'json',
                cache: true,
                url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/revenue.json',
                success: function (data) {
                    var schema = data;

                    // Create our widget container for the JSON Editor.
                    var $revenueJSONEditor = mQuery('<div>', {
                        class: 'contactclient_jsoneditor'
                    }).insertBefore($revenue);

                    // Instantiate the JSON Editor based on our schema.
                    revenueJSONEditor = new JSONEditor($revenueJSONEditor[0], {
                        schema: schema,
                        disable_collapse: true
                    });

                    $revenue.change(function () {
                        // Load the initial value if applicable.
                        var raw = mQuery(this).val(),
                            obj;
                        if (raw.length) {
                            try {
                                obj = mQuery.parseJSON(raw);
                                if (typeof obj === 'object') {
                                    revenueJSONEditor.setValue(obj);
                                }
                            }
                            catch (e) {
                                console.warn(e);
                            }
                        }
                    }).trigger('change');

                    // Persist the value to the JSON Editor.
                    revenueJSONEditor.on('change', function () {
                        var obj = revenueJSONEditor.getValue();
                        if (typeof obj === 'object') {
                            var raw = JSON.stringify(obj, null, '  ');
                            if (raw.length) {
                                // Set the textarea.
                                $revenue.val(raw);
                            }
                        }
                    });

                    $revenue.addClass('hide');
                    $revenueJSONEditor.show();
                    mQuery('label[for=contactclient_revenueSettings]').addClass('hide');
                }
            });
        }
    });
};
