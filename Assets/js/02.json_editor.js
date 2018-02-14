// Extend the bootstrap3 theme with some minor aesthetic customizations.
JSONEditor.defaults.themes.custom = JSONEditor.defaults.themes.bootstrap3.extend({
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
        el.style.cursor = 'pointer';
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
JSONEditor.defaults.options.required_by_default = true;
JSONEditor.defaults.options.expand_height = true;

// Custom validators.
JSONEditor.defaults.custom_validators.push(function (schema, value, path) {
    var errors = [];
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
    // Activate the jQuery Chosen plugin for all select fields with more than
    // 8 elements. Use "format": "select" to activate.
    if (schema.format === 'select') {
        // Get the element based on schema path.
        var selector = 'select[name=\'' + path.replace('root.', 'root[').split('.').join('][') + ']\']:not(.chosen-checked)';
        mQuery(selector).each(function () {
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
                    } else {
                        changed = false;
                    }
                });
            }
            mQuery(this).addClass('chosen-checked');
        });
    }
    return errors;
});