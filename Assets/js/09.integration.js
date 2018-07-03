// Integration screen logic. Triggered on Client change.
Mautic.contactclientIntegrationPre = function () {
    var $client = mQuery('#campaignevent_properties_config_contactclient:not(.contactclient-checked):first'),
        $overrides = mQuery('#campaignevent_properties_config_contactclient_overrides:not(.contactclient-checked):first');

    if ($client.length && $overrides.length) {

        if (typeof Mautic.contactclientIntegrationStylesLoaded === 'undefined') {
            Mautic.contactclientIntegrationStylesLoaded = true;
            mQuery('head').append('<link rel="stylesheet" href="' + mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/build/contactclient.min.css' + '" data-source="mautic" />');
        }

        var tokenSource = 'plugin:mauticContactClient:getTokens';
        if (typeof window.JSONEditor.tokenCache === 'undefined') {
            window.JSONEditor.tokenCache = {};
        }
        if (typeof window.JSONEditor.tokenCacheTypes === 'undefined') {
            window.JSONEditor.tokenCacheTypes = {};
        }
        if (typeof window.JSONEditor.tokenCacheFormats === 'undefined') {
            window.JSONEditor.tokenCacheFormats = {};
        }
        if (typeof window.JSONEditor.tokenCache[tokenSource] === 'undefined') {
            window.JSONEditor.tokenCache[tokenSource] = {};
            window.JSONEditor.tokenCacheTypes[tokenSource] = {};
            window.JSONEditor.tokenCacheFormats[tokenSource] = {};
            mQuery.ajax({
                url: mauticAjaxUrl,
                type: 'POST',
                data: {
                    action: tokenSource,
                    apiPayload: '',
                    filePayload: ''
                },
                cache: true,
                dataType: 'json',
                success: function (response) {
                    if (typeof response.tokens !== 'undefined') {
                        window.JSONEditor.tokenCache[tokenSource] = response.tokens;
                    }
                    if (typeof response.types !== 'undefined') {
                        window.JSONEditor.tokenCacheTypes[tokenSource] = response.types;
                    }
                    if (typeof response.formats !== 'undefined') {
                        window.JSONEditor.tokenCacheFormats[tokenSource] = response.formats;
                    }
                },
                error: function (request, textStatus, errorThrown) {
                    Mautic.processAjaxError(request, textStatus, errorThrown);
                },
                complete: function () {
                    Mautic.contactclientIntegration();
                }
            });
        }
        else {
            Mautic.contactclientIntegration();
        }
    }
};
Mautic.contactclientIntegration = function () {
    var $client = mQuery('#campaignevent_properties_config_contactclient:not(.contactclient-checked):first'),
        $overrides = mQuery('#campaignevent_properties_config_contactclient_overrides:not(.contactclient-checked):first'),
        $button = mQuery('#campaignevent_properties_config_contactclient_overrides_button:first'),
        $label = mQuery('label[for=campaignevent_properties_config_contactclient_overrides]:first');

    // Upgrade the textarea to json schema.
    var overridesJSONEditor,
        schema,
        overridesJSONEditorStart = function (callback) {
            if (!overridesJSONEditor || mQuery('.contactclient_jsoneditor').length < 1) {
                if (!schema) {
                    mQuery.ajax({
                        dataType: 'json',
                        cache: true,
                        url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/json/overrides.json',
                        success: function (data) {
                            schema = data;
                            return overridesJSONEditorFinish(schema, callback);
                        }
                    });
                }
                else {
                    return overridesJSONEditorFinish(schema, callback);
                }
            }
            else {
                return callback(overridesJSONEditor);
            }
        },
        overridesJSONEditorFinish = function (schema, callback) {
            // Create our widget container for the JSON Editor.
            var $overridesJSONEditor = mQuery('<div>', {
                class: 'contactclient_jsoneditor'
            }).insertBefore($overrides);

            // Instantiate the JSON Editor based on our schema.
            overridesJSONEditor = new JSONEditor($overridesJSONEditor[0], {
                schema: schema,
                disable_array_add: true,
                disable_array_delete: true,
                disable_array_reorder: true,
                disable_collapse: true
            });

            // Initial value set only. Other values will be set
            // after config merge by $overrides change.
            var raw = $overrides.val(),
                obj;
            if (raw.length) {
                try {
                    obj = mQuery.parseJSON(raw);
                    if (typeof obj === 'object') {
                        overridesJSONEditor.setValue(obj);
                    }
                }
                catch (e) {
                    console.warn(e);
                }
            }

            // Persist the value to the JSON Editor.
            overridesJSONEditor.on('change', function () {
                var obj = overridesJSONEditor.getValue();
                if (typeof obj === 'object') {
                    var raw = JSON.stringify(obj, null, 2);
                    if (raw.length) {
                        // Set the textarea.
                        $overrides.val(raw);
                    }
                }
                $overridesJSONEditor.find('div > table > tbody > tr').each(function () {
                    if (mQuery(this).find('input[name$="[enabled]"]:first:checked').length) {
                        mQuery(this).removeClass('disabled');
                    }
                    else {
                        mQuery(this).addClass('disabled');
                    }
                });
            });

            $label.removeClass('hide');

            // Expand the modal view.
            var $modal = mQuery('#CampaignEventModal:first');
            if ($modal.length) {
                var $dialog = $modal.find('.modal-dialog:first');
                $dialog.addClass('expanded');
                $modal.on('hidden.bs.modal', function () {
                    $dialog.removeClass('expanded');
                });
            }

            return callback(overridesJSONEditor);
        };

    // Get the JSON from the client selector, if not already present.
    $client.off('change').on('change', function () {
        var clientId = parseInt(mQuery(this).val()),
            $select = clientId ? mQuery('#campaignevent_properties_config_contactclient > option[value=' + clientId + ']:first') : [],
            defaultJson = $select.length ? $select.attr('data-overridable-fields') : '{}',
            currentJson = $overrides.val() ? $overrides.val() : '{}',
            newJson = defaultJson;

        $label.addClass('hide');
        $button.addClass('hide');
        // Merge defaults with whatever was configured before.
        try {
            var defaults = mQuery.parseJSON(defaultJson),
                currents = mQuery.parseJSON(currentJson),
                unique = {},
                result = [];
            mQuery.each(defaults, function (d, def) {
                if (typeof def.key !== 'undefined') {
                    unique[def.key] = {
                        key: def.key,
                        value: def.value,
                        description: def.description,
                        enabled: false
                    };
                    mQuery.each(currents, function (c, cur) {
                        if (typeof cur.key !== 'undefined' && cur.key === def.key) {
                            if (
                                (typeof cur.enabled === 'undefined') ||
                                (typeof cur.enabled !== 'undefined' && cur.enabled === true)
                            ) {
                                unique[def.key].enabled = true;
                                unique[def.key].value = cur.value;
                                unique[def.key].description = cur.description;
                            }
                        }
                    });
                }
            });
            mQuery.each(unique, function (u, uni) {
                result.push(uni);
            });
            newJson = JSON.stringify(result, null, 2);
            overridesJSONEditorStart(function (editor) {
                editor.setValue(result);
            });
        }
        catch (e) {
            console.warn('Could not merge overrides', e);
        }
        $overrides.val(newJson);
    }).trigger('change');

    // $client.addClass('contactclient-checked');
    // $overrides.addClass('contactclient-checked');
    Mautic.removeButtonLoadingIndicator($button);
};