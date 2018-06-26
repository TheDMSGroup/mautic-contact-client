// Extend the bootstrap3 theme with some minor aesthetic customizations.
function levenshtein (a, b) {
    var tmp;
    if (a.length === 0) { return b.length; }
    if (b.length === 0) { return a.length; }
    if (a.length > b.length) {
        tmp = a;
        a = b;
        b = tmp;
    }

    var i, j, res, alen = a.length, blen = b.length, row = Array(alen);
    for (i = 0; i <= alen; i++) { row[i] = i; }

    for (i = 1; i <= blen; i++) {
        res = i;
        for (j = 1; j <= alen; j++) {
            tmp = row[j - 1];
            row[j - 1] = res;
            res = b[i - 1] === a[j - 1] ? tmp : Math.min(tmp + 1, Math.min(res + 1, row[j] + 1));
        }
    }
    return res;
}

JSONEditor.defaults.themes.custom = JSONEditor.defaults.themes.bootstrap3.extend({
    // Support bootstrap-slider.
    getRangeInput: function (min, max, step) {
        var el = this._super(min, max, step);
        el.className = el.className.replace('form-control', '');
        return el;
    },
    // Make the buttons smaller and more consistent.
    getButton: function (text, icon, title) {
        var el = this._super(text, icon, title);
        if (title.indexOf('Delete') !== -1) {
            el.className = el.className.replace('btn-default', 'btn-sm btn-xs btn-danger');
        }
        else if (title.indexOf('Add') !== -1) {
            el.className = el.className.replace('btn-default', 'btn-md btn-primary');
        }
        else {
            el.className = el.className.replace('btn-default', 'btn-sm btn-xs btn-secondary');
        }
        return el;
    },
    // Pull header nav to the right.
    getHeaderButtonHolder: function () {
        var el = this.getButtonHolder();
        el.className = 'btn-group btn-group-sm btn-right';
        return el;
    },
    // Pull "new item" buttons to the left.
    getButtonHolder: function () {
        var el = document.createElement('div');
        el.className = 'btn-group btn-left';
        return el;
    },
    // Make the h3 elements clickable.
    getHeader: function (text) {
        var el = document.createElement('h3');
        el.onclick = function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $collapseButton = mQuery(this).find('> div.btn-group > button.json-editor-btn-collapse:first:visible');
            if ($collapseButton.length) {
                var el = $collapseButton[0];
                if (el) {
                    var event = new MouseEvent('click', {
                        'view': window,
                        'bubbles': false,
                        'cancelable': true
                    });
                    el.dispatchEvent(event);
                }
            }
        };
        // el.style.cursor = 'pointer';
        if (typeof text === 'string') {
            el.textContent = text;
        }
        else {
            el.appendChild(text);
        }
        return el;
    }
});

// Extend the fontawesome icon kit for minor changes.
JSONEditor.defaults.iconlibs.custom = JSONEditor.AbstractIconLib.extend({
    mapping: {
        collapse: 'caret-down',
        expand: 'caret-right',
        delete: 'times',
        edit: 'pencil',
        add: 'plus',
        cancel: 'ban',
        save: 'save',
        moveup: 'arrow-up',
        movedown: 'arrow-down'
    },
    icon_prefix: 'fa fa-'
});

// Override default settings.
JSONEditor.defaults.options.ajax = false;
JSONEditor.defaults.options.theme = 'custom';
JSONEditor.defaults.options.iconlib = 'custom';
JSONEditor.defaults.options.disable_edit_json = true;
JSONEditor.defaults.options.disable_properties = true;
JSONEditor.defaults.options.disable_array_delete_all_rows = true;
JSONEditor.defaults.options.disable_array_delete_last_row = true;
JSONEditor.defaults.options.remove_empty_properties = false;
JSONEditor.defaults.options.required_by_default = true;
JSONEditor.defaults.options.expand_height = true;
JSONEditor.defaults.options.keep_oneof_values = false;

// Custom validators.
JSONEditor.defaults.custom_validators.push(function (schema, value, path) {
    var errors = [];

    // When a textarea with option "queryBuilder" is true, render Query Builder.
    if (schema.format === 'textarea' && typeof schema.options !== 'undefined' && schema.options.queryBuilder === true) {
        mQuery('textarea[name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:first') // :not(.queryBuilder-checked)
            .each(function () {
                var $input = mQuery(this),
                    val = $input.val(),
                    rules = {},
                    error = false,
                    checked = $input.hasClass('queryBuilder-checked'),
                    timeout;

                if (!val.length) {
                    return;
                }
                try {
                    rules = mQuery.parseJSON(val);
                    if (typeof rules !== 'object') {
                        error = true;
                        errors.push({
                            path: path,
                            property: 'format',
                            message: 'This Query Builder field does not contain an object.'
                        });
                    }
                }
                catch (e) {
                    error = true;
                    errors.push({
                        path: path,
                        property: 'format',
                        message: 'Could not parse the JSON in this Query Builder field.'
                    });
                }

                if (!error) {
                    if (!checked) {
                        mQuery('<div>',
                            {
                                id: 'success-definition-' + path.split('.')[2],
                                class: 'query-builder'
                            })
                            .insertAfter($input)
                            .queryBuilder(Mautic.contactclientQBDefaultSettings)
                            // On any change to Success Definition:
                            .on('rulesChanged.queryBuilder', function () {
                                var $queryBuilder = mQuery(this);
                                clearTimeout(timeout);
                                timeout = setTimeout(function () {
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
                                }, 50);
                            });
                        $input.addClass('queryBuilder-checked');
                    }
                    else {
                        // The QueryBuilder has already been built, update
                        // value if it has changed.
                        var $queryBuilder = $input.next('.query-builder'),
                            oldRules = $queryBuilder.queryBuilder('getRules', Mautic.contactclientQBDefaultGet),
                            oldRulesString = JSON.stringify(oldRules, null, 2);
                        if (val !== oldRulesString) {
                            try {
                                $queryBuilder.queryBuilder('setRules', rules);
                            }
                            catch (e) {
                                error = true;
                                errors.push({
                                    path: path,
                                    property: 'format',
                                    message: 'This Query Builder field is invalid.'
                                });
                            }
                        }
                    }
                }
            })
            .addClass('hide');
    }

    // When a textarea with option "codeMirror" is true, render codeMirror.
    if (typeof schema.options !== 'undefined' && schema.options.codeMirror === true) {
        var selector = '[name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:first:not(.codeMirror-checked)';
        mQuery('input' + selector + ', textarea' + selector).first()
            .each(function () {
                if (schema.options.tokenSource !== 'undefined' && schema.options.tokenSource.length) {
                    var tokenSource = schema.options.tokenSource;
                }
                var $input = mQuery(this),
                    isTextarea = $input.is('textarea'),
                    hintTimer,
                    delimiter = '  ',
                    hinter = function (cm, option) {
                        return new Promise(function (accept) {
                            clearTimeout(hintTimer);
                            var addMatch = function (matches, key, value) {
                                if (key === value) {
                                    matches.push('{{ ' + key + ' }}');
                                }
                                else {
                                    matches.push('{{ ' + key + ' }}' + delimiter + value);
                                }
                            };
                            hintTimer = setTimeout(function () {
                                if (typeof window.JSONEditor.tokenCache[tokenSource] !== 'undefined') {
                                    var cursor = cm.getCursor(),
                                        line = cm.getLine(cursor.line),
                                        start = cursor.ch,
                                        end = cursor.ch;
                                    while (start && /[\w|{|\.]/.test(line.charAt(start - 1))) {
                                        --start;
                                    }
                                    while (end < line.length && /[\w|}|\.]/.test(line.charAt(end))) {
                                        ++end;
                                    }
                                    var word = line.slice(start, end).toLocaleLowerCase().replace(/[\s|{|}]/g, ''),
                                        len = word.length,
                                        matches = [];
                                    if (len >= 2) {
                                        // Exact matching keys
                                        mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                                            if (key === word) {
                                                addMatch(matches, key, value);
                                            }
                                        });
                                        // Partial matching keys.
                                        if (matches.length < 10) {
                                            mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                                                if (key.length > len) {
                                                    if (key.toLowerCase().substr(0, len) === word) {
                                                        addMatch(matches, key, value);
                                                        if (matches.length === 10) {
                                                            return false;
                                                        }
                                                    }
                                                }
                                            });
                                        }
                                        // Partial matching keys.
                                        if (matches.length < 10) {
                                            mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                                                if (value.length > len) {
                                                    if (value.toLowerCase().substr(0, len) === word) {
                                                        addMatch(matches, key, value);
                                                        if (matches.length === 10) {
                                                            return false;
                                                        }
                                                    }
                                                }
                                            });
                                        }
                                        // Containing keys.
                                        if (matches.length < 10) {
                                            mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                                                if (key.length > len) {
                                                    if (key.indexOf(word) !== -1 || word.indexOf(key) !== -1) {
                                                        addMatch(matches, key, value);
                                                        if (matches.length === 10) {
                                                            return false;
                                                        }
                                                    }
                                                }
                                            });
                                        }
                                        // Containing labels.
                                        if (matches.length < 10) {
                                            mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                                                if (value.length > len) {
                                                    if (value.toLowerCase().indexOf(word) !== -1 || word.indexOf(value.toLowerCase()) !== -1) {
                                                        addMatch(matches, key, value);
                                                        if (matches.length === 10) {
                                                            return false;
                                                        }
                                                    }
                                                }
                                            });
                                        }
                                        // Levenshtein keys.
                                        if (matches.length < 5) {
                                            mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                                                if (key.length >= len) {
                                                    if (levenshtein(word, key) < 5) {
                                                        addMatch(matches, key, value);
                                                        if (matches.length === 5) {
                                                            return false;
                                                        }
                                                    }
                                                }
                                            });
                                        }
                                        // Levenshtein labels.
                                        if (matches.length < 5) {
                                            mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                                                if (value.length >= len) {
                                                    if (levenshtein(word, value) < 5) {
                                                        addMatch(matches, key, value);
                                                        if (matches.length === 5) {
                                                            return false;
                                                        }
                                                    }
                                                }
                                            });
                                        }
                                        if (matches.length) {
                                            setTimeout(function(){
                                                listener(cm);
                                            }, 100);
                                            return accept({
                                                list: matches,
                                                from: CodeMirror.Pos(cursor.line, start),
                                                to: CodeMirror.Pos(cursor.line, end)
                                            });
                                        }
                                    }
                                    return accept(null);
                                }
                            }, 500);
                        });
                    },
                    listener = function (cm) {
                        // Listen for selection of a hint autocomplete box.
                        if (
                            cm.state.completionActive
                            && typeof cm.state.completionActive.data !== 'undefined'
                            && cm.state.completionActive.data
                        ) {
                            CodeMirror.on(cm.state.completionActive.data, 'pick', function (completion, element) {
                                var parts = completion.split(delimiter);
                                if (parts.length) {
                                    cm.replaceRange(parts[0], cm.state.completionActive.data.from, {
                                        'ch': cm.state.completionActive.data.from.ch + completion.length,
                                        'line': cm.state.completionActive.data.from.line
                                    }, 'complete');
                                }
                            });
                        }
                    },
                    options = mQuery.extend(
                        isTextarea ? {
                            mode: 'json/mustache',
                            lint: 'json',
                            theme: 'cc',
                            gutters: ['CodeMirror-lint-markers'],
                            lintOnChange: true,
                            matchBrackets: false,
                            autoCloseBrackets: true,
                            lineNumbers: true,
                            lineWrapping: true
                        } : {
                            mode: 'json/mustache',
                            theme: 'cc',
                            gutters: [],
                            lintOnChange: false,
                            matchBrackets: false,
                            autoCloseBrackets: true,
                            lineNumbers: false,
                            lineWrapping: false
                        }, {
                            hintOptions: {
                                hint: function (cm, options) { return hinter(cm, options); }
                            }
                        }),
                    // Wait till the textarea is visible before codemirror.
                    pollVisibility = setInterval(function () {
                        if ($input.is(':visible')) {
                            clearInterval(pollVisibility);
                            var cm = CodeMirror.fromTextArea($input[0], options);
                            cm.on('change', function (cm) {
                                // Push changes to the original field.
                                $input.val(cm.getValue());
                                if ('createEvent' in document) {
                                    var event = document.createEvent('HTMLEvents');
                                    event.initEvent('change', false, true);
                                    $input[0].dispatchEvent(event);
                                }
                                else {
                                    $input[0].fireEvent('onchange');
                                }
                            });
                            cm.on('keyup', function (cm, event) {
                                // Autocomplete suggestions on keyup.
                                if (!cm.state.completionActive && event.keyCode !== 13) {
                                    CodeMirror.commands.autocomplete(cm, null, {
                                        completeSingle: false
                                    });
                                }
                            });
                            $input.addClass('codeMirror-active');
                            // @todo - Remove this hack.
                            if (isTextarea) {
                                $input.parent().parent().parent().parent().find('div[data-schemaid="requestFormat"]:first select:first').trigger('change');
                            }
                        }
                    }, 300);
            }).addClass('codeMirror-checked');
    }

    // Annual/fixed date support (not currently used).
    if (schema.format === 'datestring') {
        if (!/^[0-9|yY]{4}-[0-9]{1,2}-[0-9]{1,2}$/.test(value) && !/^[0-9]{1,2}-[0-9]{1,2}$/.test(value)) {
            // Errors must be an object with `path`, `property`, and `message`
            errors.push({
                path: path,
                property: 'format',
                message: 'Dates should be in ISO format as YYYY-MM-DD or MM-DD for repeating dates'
            });
        }
    }

    // Single fixed date selector.
    if (schema.format === 'date') {
        mQuery('input[name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:not(.date-checked)').each(function () {
            Mautic.activateDateTimeInputs(mQuery(this), 'date');

            var changed = false;
            // Make sure an event fires passing value through
            mQuery(this).on('change', function (o) {
                if (!changed) {
                    if ('createEvent' in document) {
                        changed = true;
                        var event = document.createEvent('HTMLEvents');
                        event.initEvent('change', false, true);
                        mQuery(this)[0].dispatchEvent(event);
                    }
                    else {
                        mQuery(this)[0].fireEvent('onchange');
                    }
                }
                else {
                    changed = false;
                }
            });

        }).addClass('date-checked');
    }

    // Activate the jQuery Chosen plugin for all select fields with more than
    // 8 elements. Use "format": "select" to activate.
    if (schema.format === 'select') {
        // Get the element based on schema path.
        mQuery('select[name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:not(.chosen-checked)').each(function () {
            if (mQuery(this).children('option').length > 8) {
                var $select = mQuery(this),
                    changed = false;
                $select.chosen({
                    width: '100%',
                    allow_single_deselect: false,
                    include_group_label_in_selected: false,
                    search_contains: true
                }).change(function (e) {
                    // Feed back the change to JSONEditor (little tricky).
                    if (!changed) {
                        e.stopPropagation();
                        e.preventDefault();
                        if ('createEvent' in document) {
                            changed = true;
                            var event = document.createEvent('HTMLEvents');
                            event.initEvent('change', false, true);
                            $select[0].dispatchEvent(event);
                        }
                        else {
                            $select[0].fireEvent('onchange');
                        }
                    }
                    else {
                        changed = false;
                    }
                });
            }
        }).addClass('chosen-checked');
    }

    // Improve the range slider with bootstrap sliders.
    if (schema.format === 'range') {
        // Get the element based on schema path.
        mQuery('input[type=\'range\'][name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:not(.slider-checked)').each(function () {
            var $slider = mQuery(this),
                min = parseInt(mQuery(this).attr('min')),
                max = parseInt(mQuery(this).attr('max')),
                step = parseInt(mQuery(this).attr('step')),
                value = parseInt(mQuery(this).val()),
                options = {
                    'min': min,
                    'max': max,
                    'value': value,
                    'step': step
                };
            if (min === 0 && max === 100) {
                options.formatter = function (val) {
                    return val + '%';
                };
            }
            var slider = new Slider(mQuery(this)[0], options);
            slider.on('change', function (o) {
                if ('createEvent' in document) {
                    var event = document.createEvent('HTMLEvents');
                    event.initEvent('change', false, true);
                    $slider[0].dispatchEvent(event);
                }
                else {
                    $slider[0].fireEvent('onchange');
                }
            });
        }).addClass('slider-checked');
    }

    // Set html5 "required' fields by the option "notBlank"
    if (schema.type === 'string' && typeof schema.options !== 'undefined' && typeof schema.options.notBlank !== 'undefined' && schema.options.notBlank === true) {
        // mQuery('input[type=\'text\'][name=\'' + path.replace('root.',
        // 'root[').split('.').join('][') +
        // ']\']:first:not([required])').each(function () {
        // mQuery(this).prop('required', true); });
        if (value.replace(/^\s+|\s+$/gm, '') === '') {
            errors.push({
                path: path,
                property: 'format',
                message: 'This field cannot be left blank'
            });
        }
    }

    // Add placeholders
    if (schema.type === 'string' && typeof schema.options !== 'undefined' && typeof schema.options.placeholder !== 'undefined') {
        mQuery('input[type=\'text\'][name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:first:not([placeholder])').each(function () {
            mQuery(this).prop('placeholder', schema.options.placeholder);
        });
    }

    return errors;
});