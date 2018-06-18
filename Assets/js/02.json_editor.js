// Extend the bootstrap3 theme with some minor aesthetic customizations.
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

/**
 * Convert a standard text field to a tagEditor field using predefined tokens.
 *
 * @param $text
 * @param tokenSource
 */
JSONEditor.createTagEditor = function ($text, tokenSource, tokenPlaceholder) {
    var allowedTagArr = [],
        changed = false;
    $text.tagEditor({
        placeholder: tokenPlaceholder,
        allowedTags: function () {
            if (!allowedTagArr.length && typeof window.JSONEditor.tokenCache[tokenSource] !== 'undefined') {
                mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                    allowedTagArr.push('{{' + key + '}}');
                });
            }
            return allowedTagArr;
        },
        autocomplete: {
            minLength: 2,
            source: function (request, response) {
                var tokens = [];
                if (typeof window.JSONEditor.tokenCache[tokenSource] !== 'undefined') {
                    var regex = new RegExp(request.term.replace(/\{|\}/g, ''), 'i');
                    mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                        if (regex.test(key) || regex.test(value)) {
                            tokens.push({
                                label: value,
                                value: '{{' + key + '}}'
                            });
                        }
                    });
                }
                response(tokens);
            },
            delay: 120
        },
        onChange: function (el, ed, tag_list) {
            if (!changed) {
                if ('createEvent' in document) {
                    // changed = true;
                    var event = document.createEvent('HTMLEvents');
                    event.initEvent('change', false, true);
                    $text[0].dispatchEvent(event);
                }
                else {
                    $text[0].fireEvent('onchange');
                }
            }
            else {
                // changed = true;
            }
        },
        beforeTagSave: function () {},
        beforeTagDelete: function () {}
    });
};

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
    if (schema.format === 'textarea' && typeof schema.options !== 'undefined' && schema.options.codeMirror === true) {
        mQuery('textarea[name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:first:not(.codeMirror-checked)')
            .each(function () {
                if (schema.options.tokenSource !== 'undefined' && schema.options.tokenSource.length) {
                    var tokenSource = schema.options.tokenSource;
                }
                var $input = mQuery(this),
                    allowedTagArr = [],
                    hintTimer,
                    // Wait till the textarea is visible before codemirror.
                    pollVisibility = setInterval(function () {
                        if ($input.is(':visible')) {
                            var cm = CodeMirror.fromTextArea($input[0], {
                                mode: {
                                    name: 'javascript',
                                    json: true
                                },
                                lint: 'json',
                                theme: 'material',
                                gutters: ['CodeMirror-lint-markers'],
                                lintOnChange: true,
                                matchBrackets: true,
                                autoCloseBrackets: true,
                                lineNumbers: true,
                                lineWrapping: true,
                                hintOptions: {
                                    hint: function (cm, option) {
                                        return new Promise(function (accept) {
                                            clearTimeout(hintTimer);
                                            hintTimer = setTimeout(function () {
                                                if (!allowedTagArr.length && typeof tokenSource !== 'undefined') {
                                                    if (typeof window.JSONEditor.tokenCache[tokenSource] !== 'undefined') {
                                                        mQuery.each(window.JSONEditor.tokenCache[tokenSource], function (key, value) {
                                                            allowedTagArr.push(key);
                                                        });
                                                    }
                                                }
                                                if (allowedTagArr.length) {
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
                                                    var word = line.slice(start, end).toLowerCase().replace(/[\s|{|}]/g, ''),
                                                        len = word.length,
                                                        matches = [];
                                                    if (len >= 2) {
                                                        for (var i = 0; i < allowedTagArr.length; i++) {
                                                            if (
                                                                allowedTagArr[i].length >= len
                                                                && allowedTagArr[i].substr(0, len) === word
                                                            // &&
                                                            // allowedTagArr[i]
                                                            // !== word
                                                            ) {
                                                                matches.push('{{' + allowedTagArr[i] + '}}');
                                                            }
                                                        }
                                                        if (matches.length) {
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
                                    }
                                }
                            });
                            cm.on('change', function (cm) {
                                // Push changes to the textarea and ensure
                                // event fires.
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
                            clearInterval(pollVisibility);
                            $input.addClass('codeMirror-active');
                            // @todo - Remove this hack.
                            $input.parent().parent().parent().parent().find('div[data-schemaid="requestFormat"]:first select:first').trigger('change');
                        }
                    }, 250);
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

    // Add support for a token text field.
    if (schema.type === 'string' && typeof schema.options !== 'undefined' && typeof schema.options.tokenSource !== 'undefined' && schema.options.tokenSource.length) {

        if (typeof window.JSONEditor.tokenCache === 'undefined') {
            window.JSONEditor.tokenCache = {};
        }

        // Re-render any who's values have been altered by reordering or
        // deletion.
        mQuery('input[type=\'text\'][name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:first.tag-editor-hidden-src').each(function () {
            var $text = mQuery(this),
                $tagEditor = mQuery(this).parent().find('ul.tag-editor:first');
            if ($tagEditor.length) {
                var tagValue = $tagEditor.data('tags').join('');
                if ($text.val() !== tagValue) {
                    $tagEditor.remove();
                    $text.removeClass('tokens-checked').removeClass('tag-editor-hidden-src');
                }
            }
        });

        mQuery('input[type=\'text\'][name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:first:not(.tokens-checked)').each(function () {
            var $text = mQuery(this),
                tokenSource = schema.options.tokenSource,
                tokenPlaceholder = (typeof schema.options.tokenPlaceholder !== 'undefined' ? schema.options.tokenPlaceholder : null);

            // $text.data('tokenSource', tokenSource);

            if (typeof window.JSONEditor.tokenCache[tokenSource] === 'undefined') {
                window.JSONEditor.tokenCache[tokenSource] = {};
                mQuery.ajax({
                    url: mauticAjaxUrl,
                    type: 'POST',
                    data: {
                        action: tokenSource
                    },
                    cache: true,
                    dataType: 'json',
                    success: function (response) {
                        if (typeof response.tokens !== 'undefined') {
                            window.JSONEditor.tokenCache[tokenSource] = response.tokens;
                        }
                        if (!mQuery.isEmptyObject(window.JSONEditor.tokenCache[tokenSource])) {
                            JSONEditor.createTagEditor($text, tokenSource, tokenPlaceholder);
                        }
                        else {
                            console.log('No tokens found for ' + tokenSource);
                        }
                    },
                    error: function (e) {
                        console.warn('Could not retrieve tokens for ' + tokenSource, e);
                    }
                });
            }
            else {
                var tries = 0,
                    checkTokens = setInterval(function () {
                        tries++;
                        if (tries > 300) {
                            console.warn('Took too long to retrieve tokens for ' + tokenSource);
                            clearInterval(checkTokens);
                        }
                        if (!mQuery.isEmptyObject(window.JSONEditor.tokenCache[tokenSource])) {
                            clearInterval(checkTokens);
                            JSONEditor.createTagEditor($text, tokenSource, tokenPlaceholder);
                        }
                    }, 100);
            }

        }).addClass('tokens-checked');
    }

    // Set html5 "required' fields by the option "notBlank"
    if (schema.type === 'string' && typeof schema.options !== 'undefined' && typeof schema.options.notBlank !== 'undefined' && schema.options.notBlank === true) {
        // mQuery('input[type=\'text\'][name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:first:not([required])').each(function () {
        //     mQuery(this).prop('required', true);
        // });
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