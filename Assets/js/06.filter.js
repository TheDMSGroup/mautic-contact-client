// Filter field.
Mautic.contactclientFilter = function () {
    Mautic.contactclientPreloadTokens(function () {
        Mautic.contactclientFilterStart();
    });
};
Mautic.contactclientFilterStart = function () {
    var $input = mQuery('#contactclient_filter:first:not(.queryBuilder-checked)');
    if ($input.length) {
        var QBSettings = mQuery.extend({}, Mautic.contactclientQBDefaultSettings),
            tokenSource = 'plugin:mauticContactClient:getTokens',
            timeout,
            chosenSettings = {
                allow_single_deselect: true,
                search_contains: true
            };
        QBSettings.filters = [];
        // Get field/token list and use that as our filters for the Query
        // Builder.
        if (typeof window.JSONEditor.tokenCache[tokenSource] == 'object') {
            mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                if (key.indexOf('payload.') === 0) {
                    return;
                }
                // Default operators.
                var internalType = 'text',
                    type = 'string',
                    operators = Mautic.contactclientQBDefaultOps;

                // Check for specific type/operator sets.
                if (typeof window.JSONEditor.tokenCacheTypes[tokenSource][key] == 'string') {
                    internalType = window.JSONEditor.tokenCacheTypes[tokenSource][key];
                    switch (internalType) {
                        case 'number':
                            type = 'double';
                            operators = [
                                'equal',
                                'not_equal',
                                'less',
                                'less_or_equal',
                                'greater',
                                'greater_or_equal'
                            ];
                            break;

                        case 'date':
                            type = 'date';
                            operators = [
                                'equal',
                                'not_equal',
                                'less',
                                'less_or_equal',
                                'greater',
                                'greater_or_equal'
                            ];
                            break;

                        case 'datetime':
                            type = 'datetime';
                            operators = [
                                'equal',
                                'not_equal',
                                'less',
                                'less_or_equal',
                                'greater',
                                'greater_or_equal'
                            ];
                            break;

                        case 'boolean':
                            type = 'boolean';
                            operators = [
                                'equal',
                                'not_equal'
                            ];
                            break;
                    }
                }
                QBSettings.filters.push({
                    id: key,
                    label: value + ' (' + internalType.substring(0, 1).toUpperCase() + internalType.substring(1, internalType.length) + ')',
                    type: type,
                    operators: operators
                });
            });
        }
        if (!QBSettings.filters.length) {
            return;
        }
        mQuery('<div>',
            {
                id: 'filter-definition',
                class: 'query-builder'
            })
            .insertAfter($input)
            .queryBuilder(QBSettings)
            .on('rulesChanged.queryBuilder', function () {
                var $queryBuilder = mQuery(this);
                clearTimeout(timeout);
                timeout = setTimeout(function () {
                    $parent = $queryBuilder.parent();
                    rules = $queryBuilder.queryBuilder('getRules', Mautic.contactclientQBDefaultGet);
                    var rulesString = JSON.stringify(rules, null, 2);

                    if ($input.val() !== rulesString) {
                        $input.val(rulesString);
                        if ('createEvent' in document) {
                            var event = document.createEvent('HTMLEvents');
                            event.initEvent('change', false, true);
                            $input[0].dispatchEvent(event);
                        }
                        else {
                            $input[0].fireEvent('onchange');
                        }
                    }
                    $parent.find('input[type="text"]').each(function () {
                        mQuery(this).on('keypress', function (e) {
                            var charCode = (typeof e.which === 'number') ? e.which : e.keyCode;
                            if (charCode === 13) {
                                e.preventDefault();
                                e.stopPropagation();
                                return false;
                            }
                        });
                    });
                    $parent.find('select').chosen(chosenSettings);
                }, 50);
            })
            .trigger('rulesChanged.queryBuilder');
        $input.addClass('queryBuilder-checked');
    }
};