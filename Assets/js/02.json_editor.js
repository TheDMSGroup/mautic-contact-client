// Extend the bootstrap3 theme with some minor aesthetic customizations.
JSONEditor.defaults.themes.custom = JSONEditor.defaults.themes.bootstrap3.extend({
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

// Add a "query" field type using the Query Builder.
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
                filters: Mautic.contactclientSuccessDefinitionFiltersDefault,
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

// Override default settings.
JSONEditor.defaults.options.ajax = false;
JSONEditor.defaults.options.theme = 'custom';
JSONEditor.defaults.options.iconlib = 'fontawesome4';
JSONEditor.defaults.options.disable_edit_json = true;
JSONEditor.defaults.options.disable_properties = true;
JSONEditor.defaults.options.disable_array_delete_all_rows = true;
JSONEditor.defaults.options.disable_array_delete_last_row = true;
JSONEditor.defaults.options.required_by_default = true;
JSONEditor.defaults.options.expand_height = true;