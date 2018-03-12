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

// Add a "query" field type using the Query Builder.
if (typeof Mautic.successDefinitionFiltersDefault !== 'undefined') {
    JSONEditor.defaults.editors.query = JSONEditor.defaults.editors.string.extend({
        postBuild: function () {
            // Default success rules (if the status
            // code is 200).
            var element = this,
                successDefinitionRules = {
                    condition: 'AND',
                    rules: [{
                        id: 'status',
                        operator: 'equal',
                        value: '200'
                    }]
                };

            // Load a saved value if relevant.
            if (this.input.value) {
                if (typeof this.input.value === 'object') {
                    successDefinitionRules = this.input.value;
                }
                else {
                    try {
                        var obj = mQuery.parseJSON(this.input.value);
                    }
                    catch (e) {
                        console.warn('Invalid JSON in success definition');
                    }
                    if (typeof obj === 'object' && obj.length > 0) {
                        successDefinitionRules = obj;
                    }
                }
            }
            // Progressively Enhance the textarea into
            // a Query Builder.
            mQuery('<div>', {
                id: 'success-definition-' + this.parent.parent.parent.key,
                class: 'query-builder'
            }).insertAfter(element.input)
                .queryBuilder({
                    plugins: {
                        'sortable': {
                            icon: 'fa fa-sort'
                        },
                        'bt-tooltip-errors': null
                    },
                    filters: Mautic.successDefinitionFiltersDefault,
                    icons: {
                        add_group: 'fa fa-plus',
                        add_rule: 'fa fa-plus',
                        remove_group: 'fa fa-times',
                        remove_rule: 'fa fa-times',
                        sort: 'fa fa-sort',
                        error: 'fa fa-exclamation-triangle'
                    },
                    rules: successDefinitionRules
                })
                // On any change to Success Definition:
                .on('afterDeleteGroup.queryBuilder afterUpdateRuleFilter.queryBuilder afterAddRule.queryBuilder afterDeleteRule.queryBuilder afterUpdateRuleValue.queryBuilder afterUpdateRuleOperator.queryBuilder afterUpdateGroupCondition.queryBuilder', function () {
                    // Save the value to the hidden
                    // textarea.
                    var rules = mQuery(this).queryBuilder('getRules', {
                        get_flags: true,
                        skip_empty: true,
                        allow_invalid: true
                    });
                    element.setValue(rules);
                })
                .parent().find('textarea').addClass('hide');
        }
    });
}

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

// Custom validators.
JSONEditor.defaults.custom_validators.push(function (schema, value, path) {
    var errors = [];

    // When a textarea with option "codeMirror" is true, render codeMirror.
    if (schema.format === 'textarea' && typeof schema.options !== 'undefined' && schema.options.codeMirror === true) {
        mQuery('textarea[name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:first:visible:not(.codeMirror-checked)')
            .each(function () {
                var $input = mQuery(this);
                CodeMirror.fromTextArea($input[0], {
                    mode: {
                        name: 'javascript',
                        json: true
                    },
                    theme: 'material',
                    gutters: ['CodeMirror-lint-markers'],
                    lint: 'json',
                    lintOnChange: true,
                    matchBrackets: true,
                    autoCloseBrackets: true,
                    lineNumbers: true,
                    extraKeys: {'Ctrl-Space': 'autocomplete'},
                    lineWrapping: true
                });
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
        function tagEditor ($text, tokenSource) {
            var allowedTagArr = [];
            $text.tagEditor({
                placeholder: (typeof schema.options.tokenPlaceholder !== 'undefined' ? schema.options.tokenPlaceholder : null),
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
                    if ('createEvent' in document) {
                        var event = document.createEvent('HTMLEvents');
                        event.initEvent('change', false, true);
                        $text[0].dispatchEvent(event);
                        // console.log('Entered: ' + tag_list.join(''));
                    }
                    else {
                        $text[0].fireEvent('onchange');
                    }
                },
                beforeTagSave: function () {},
                beforeTagDelete: function () {}
            });
        }

        if (typeof window.JSONEditor.tokenCache === 'undefined') {
            window.JSONEditor.tokenCache = {};
        }

        mQuery('input[type=\'text\'][name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:first:not(.tokens-checked)').each(function () {
            var $text = mQuery(this),
                tokenSource = schema.options.tokenSource;
            $text.data('tokenSource', tokenSource);

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
                    }
                });
            }
            tagEditor($text, tokenSource);

        }).addClass('tokens-checked');
    }

    return errors;
});