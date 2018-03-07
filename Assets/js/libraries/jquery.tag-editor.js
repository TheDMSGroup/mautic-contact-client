/*
	jQuery tagEditor v1.0.21
    Copyright (c) 2014 Simon Steinberger / Pixabay
    GitHub: https://github.com/Pixabay/jQuery-tagEditor
	License: http://www.opensource.org/licenses/mit-license.php
*/

/**
 *  @todo - Tags do not automatically conjoin if normal text.
 *  @todo - No way to autoselect all text.
 */

(function ($) {
    // auto grow input (stackoverflow.com/questions/931207)
    $.fn.tagEditorInput = function () {
        var e = $(this),
            n = parseInt(e.css('fontSize')),
            i = $('<span/>').css({
                position: 'absolute',
                top: -9999,
                left: -9999,
                width: 'auto',
                fontSize: e.css('fontSize'),
                fontFamily: e.css('fontFamily'),
                fontWeight: e.css('fontWeight'),
                letterSpacing: e.css('letterSpacing'),
                whiteSpace: 'nowrap'
            }),
            resize = function () {
                var t = e.val(),
                    s = i.text(t).width() + n;
                e.css({
                    'margin-right': -n,
                    'width': t.length > 4 ? s : s + n
                });
            };
        return i.insertAfter(e), e.bind('keydown keyup blur focus update', resize);
    };

    // plugin with val as parameter for public methods
    $.fn.tagEditor = function (options, val, blur) {

        function splitTags (input) {
            var matches = input.match(/{{\s*[#|\/]?[\w\.]+\s*}}|[^{}]+/g);
            return matches ? matches : [];
        }

        function isMustache (input) {
            if (!input) {
                return false;
            }
            var matches = input.match(/{{\s*[#|\/]?[\w\.]+\s*}}/);
            return !!matches;
        }

        function className (input) {
            if (o.allowedTags) {
                if (input.indexOf('{{#') === 0 || input.indexOf('{{/') === 0) {
                    return 'warn';
                }
                if (o.allowedTags instanceof Array) {
                    if (mQuery.inArray(input, o.allowedTags) === -1) {
                        return 'danger';
                    }
                }
                if (o.allowedTags instanceof Function) {
                    if (mQuery.inArray(input, o.allowedTags()) === -1) {
                        return 'danger';
                    }
                }
            }
            return '';
        }

        // helper
        function escape (tag, addSpans) {
            if (typeof addSpans === 'undefined') {
                addSpans = true;
            }
            // Strip initial HTML.
            tag = $('<div/>').text(tag).html();
            // Wrap mustache tags.
            if (addSpans) {
                tag = tag.replace('{{', '<span>{{</span>').replace('}}', '<span>}}</span>');
            }
            return tag;
        }

        // build options dictionary with default values
        var blur_result, o = $.extend({}, $.fn.tagEditor.defaults, options),
            selector = this;

        // public methods
        if (typeof options === 'string') {
            // depending on selector, response may contain tag lists of
            // multiple editor instances
            var response = [];
            selector.each(function () {
                // the editor is the next sibling to the hidden, original field
                var el = $(this), o = el.data('options'),
                    ed = el.next('.tag-editor');
                if (options === 'getTags') {
                    response.push({
                        field: el[0],
                        editor: ed,
                        tags: ed.data('tags')
                    });
                }
                else if (options === 'addTag') {
                    if (o.maxTags && ed.data('tags').length >= o.maxTags) {
                        return false;
                    }
                    // insert new tag
                    if (isMustache(val)) {
                        $('<li><div class="tag-editor-tag ' + className(val) + '"></div><div class="tag-editor-delete ' + className(val) + '"><i></i></div></li>').appendTo(ed).find('.tag-editor-tag')
                            .html('<input type="text" maxlength="' + o.maxLength + '">').addClass('active').find('input').val(val).blur();
                    }
                    else {
                        $('<li><div class="tag-editor-tag normal"></div></li>').appendTo(ed).find('.tag-editor-tag')
                            .html('<input type="text" maxlength="' + o.maxLength + '">').addClass('active').find('input').val(val).blur();
                    }
                    if (!blur) {
                        ed.click();
                    }
                    else {
                        $('.placeholder', ed).remove();
                    }
                }
            });
            return options === 'getTags' ? response : this;
        }

        // delete selected tags on backspace, delete, ctrl+x
        if (window.getSelection) {
            $(document).off('keydown.tag-editor').on('keydown.tag-editor', function (e) {
                if (e.which === 8 || e.which === 46 || e.ctrlKey && e.which === 88) {
                    try {
                        var sel = getSelection(),
                            el = document.activeElement.tagName !== 'INPUT' ? $(sel.getRangeAt(0).startContainer.parentNode).closest('.tag-editor') : 0;
                    }
                    catch (e) { el = 0; }
                    if (sel.rangeCount > 0 && el && el.length) {
                        var tags = [],
                            splits = sel.toString().split(el.prev().data('options').dregex);
                        for (var i = 0; i < splits.length; i++) {
                            var tag = $.trim(splits[i]);
                            if (tag) {
                                tags.push(tag);
                            }
                        }
                        $('.tag-editor-tag', el).each(function () {
                            if (~$.inArray($(this).text(), tags)) {
                                $(this).closest('li').find('.tag-editor-delete').click();
                            }
                        });
                        return false;
                    }
                }
            });
        }

        return selector.each(function () {
            var el = $(this),
                tag_list = []; // cache current tags

            // create editor (ed) instance
            var ed = $('<ul ' + (o.clickDelete ? 'oncontextmenu="return false;" ' : '') + 'class="tag-editor ' + el.attr('class') + '"></ul>').insertAfter(el);
            el.addClass('tag-editor-hidden-src') // hide original field
                .data('options', o) // set data on hidden field
                .on('focus.tag-editor', function () {
                    ed.click();
                });

            // add dummy item for min-height on empty editor
            ed.append('<li style="width:1px">&nbsp;</li>');

            // markup for new tag
            var new_tag = '<li><div class="tag-editor-tag normal"></div></div></li>';

            // helper: update global data
            function set_placeholder () {
                if (o.placeholder && !tag_list.length && !$('.deleted, .placeholder, input', ed).length) {
                    ed.append('<li class="placeholder"><div>' + o.placeholder + '</div></li>');
                }
            }

            // helper: update global data
            function update_globals (init) {
                var old_tags = tag_list.join('');
                tag_list = $('.tag-editor-tag:not(.deleted)', ed).map(function (i, e) {
                    var val = $(this).hasClass('active') ? $(this).find('input').val() : $(e).text();
                    if (val) {
                        return val;
                    }
                }).get();
                tag_list = splitTags(tag_list.join(''));
                ed.data('tags', tag_list);
                el.val(tag_list.join(''));
                // change callback except for plugin init
                if (!init) {
                    if (old_tags !== tag_list.join('')) {
                        o.onChange(el, ed, tag_list);
                    }
                }
                set_placeholder();
            }

            ed.click(function (e, closest_tag) {
                var d, dist = 99999, loc;

                // do not create tag when user selects tags by text selection
                if (window.getSelection && !getSelection()) {
                    return;
                }

                if (o.maxTags && ed.data('tags').length >= o.maxTags) {
                    ed.find('input').blur();
                    return false;
                }

                blur_result = true;
                $('input:focus', ed).blur();
                if (!blur_result) {
                    return false;
                }
                blur_result = true;

                // always remove placeholder on click
                $('.placeholder', ed).remove();
                if (closest_tag && closest_tag.length) {
                    loc = 'before';
                }
                else {
                    // calculate tag closest to click position
                    $('.tag-editor-tag', ed).each(function () {
                        var tag = $(this), to = tag.offset(), tag_x = to.left,
                            tag_y = to.top;
                        if (e.pageY >= tag_y && e.pageY <= tag_y + tag.height()) {
                            if (e.pageX < tag_x) {
                                loc = 'before', d = tag_x - e.pageX;
                            }
                            else {
                                loc = 'after', d = e.pageX - tag_x - tag.width();
                            }
                            if (d < dist) {
                                dist = d, closest_tag = tag;
                            }
                        }
                    });
                }

                if (loc === 'before') {
                    $(new_tag).insertBefore(closest_tag.closest('li')).find('.tag-editor-tag').click();
                }
                else if (loc === 'after') {
                    $(new_tag).insertAfter(closest_tag.closest('li')).find('.tag-editor-tag').click();
                }
                else // empty editor
                {
                    $(new_tag).appendTo(ed).find('.tag-editor-tag').click();
                }
                return false;
            });

            ed.on('click', '.tag-editor-delete', function (e) {
                // delete icon is hidden when input is visible; place cursor
                // near invisible delete icon on click
                if ($(this).prev().hasClass('active')) {
                    $(this).closest('li').find('input').caret(-1);
                    return false;
                }

                var li = $(this).closest('li'),
                    tag = li.find('.tag-editor-tag');
                if (o.beforeTagDelete(el, ed, tag_list, tag.text()) === false) {
                    return false;
                }
                tag.addClass('deleted').animate({
                    width: 0,
                    opacity: 0
                }, o.animateDelete, function () {
                    li.remove();
                    set_placeholder();
                });
                update_globals();
                return false;
            });

            // delete on right mouse click or ctrl+click
            if (o.clickDelete) {
                ed.on('mousedown', '.tag-editor-tag', function (e) {

                    if (e.ctrlKey || e.which > 1) {
                        var li = $(this).closest('li'),
                            tag = li.find('.tag-editor-tag');
                        if (o.beforeTagDelete(el, ed, tag_list, tag.text()) === false) {
                            return false;
                        }
                        tag.addClass('deleted').animate({
                            width: 0,
                            opacity: 0
                        }, o.animateDelete, function () {
                            li.remove();
                            set_placeholder();
                        });
                        update_globals();
                        return false;
                    }
                });
            }

            ed.on('click', '.tag-editor-tag', function (e) {
                // delete on right click or ctrl+click -> exit
                if (o.clickDelete && (e.ctrlKey || e.which > 1)) {
                    return false;
                }

                if (!$(this).hasClass('active')) {
                    var tag = $(this).text();
                    // guess cursor position in text input
                    var left_percent = Math.abs(($(this).offset().left - e.pageX) / $(this).width()),
                        caret_pos = parseInt(tag.length * left_percent),
                        input = $(this).html('<input type="text" maxlength="' + o.maxLength + '" value="' + escape(tag, false) + '">').addClass('active').find('input');
                    input.data('old_tag', tag).tagEditorInput().focus().caret(caret_pos);
                    if (o.autocomplete) {
                        var aco = $.extend({}, o.autocomplete);
                        // extend user provided autocomplete select method
                        var ac_select = 'select' in aco ? o.autocomplete.select : '';
                        aco.select = function (e, ui) {
                            if (ac_select) {
                                ac_select(e, ui);
                            }
                            setTimeout(function () {

                                ed.trigger('click', [$('.active', ed).find('input').closest('li').next('li').find('.tag-editor-tag')]);
                            }, 20);
                        };
                        input.autocomplete(aco);
                    }
                }
                return false;
            });

            // helper: split into multiple tags, e.g. after paste
            function tagCleanup (input) {

                var li = input.closest('li'),
                    sub_tags = splitTags(input.val()),
                    old_tag = input.data('old_tag'),
                    old_tags = tag_list.slice(0),
                    exceeded = false,
                    cb_val;

                if (!sub_tags) {
                    return;
                }
                for (var i = 0; i < sub_tags.length; i++) {
                    tag = sub_tags[i].slice(0, o.maxLength);
                    cb_val = o.beforeTagSave(el, ed, old_tags, old_tag, tag);
                    tag = cb_val || tag;
                    if (cb_val === false || !tag) {
                        continue;
                    }
                    old_tags.push(tag);
                    if (isMustache(tag)) {
                        li.before('<li><div class="tag-editor-tag' + className(tag) + '">' + escape(tag) + '</div><div class="tag-editor-delete' + className(tag) + '"><i></i></div></li>');
                    }
                    else {
                        li.before('<li><div class="tag-editor-tag normal">' + escape(tag) + '</div></li>');
                    }
                    if (o.maxTags && old_tags.length >= o.maxTags) {
                        exceeded = true;
                        break;
                    }
                }
                input.attr('maxlength', o.maxLength).removeData('old_tag').val('');
                if (exceeded) {
                    input.blur();
                }
                else {
                    input.focus();
                }
                update_globals();
            }

            ed.on('blur', 'input', function (e) {

                e.stopPropagation();
                var input = $(this),
                    old_tag = input.data('old_tag'),
                    tags = splitTags(input.val()),
                    tag = tags.join('');

                if (!tag) {
                    if (old_tag && o.beforeTagDelete(el, ed, tag_list, old_tag) === false) {
                        input.val(old_tag).focus();
                        blur_result = false;
                        update_globals();
                        return;
                    }
                    try { input.closest('li').remove(); }
                    catch (e) {}
                    if (old_tag) {
                        update_globals();
                    }
                }
                else if (tag !== old_tag) {
                    var cb_val = o.beforeTagSave(el, ed, tag_list, old_tag, tags);
                    tags = cb_val || tags;
                    if (cb_val === false) {
                        if (old_tag) {
                            input.val(old_tag).focus();
                            blur_result = false;
                            update_globals();
                            return;
                        }
                        try { input.closest('li').remove(); }
                        catch (e) {}
                        if (old_tag) {
                            update_globals();
                        }
                    }
                    else {
                        // try { input.closest('li').remove(); }
                        // catch (e) {}
                    }

                    var elements = [];
                    for (var t = 0; t < tags.length; t++) {
                        if (isMustache(tags[t])) {
                            elements.push('<div class="tag-editor-tag '+ className(tags[t]) + '">' + escape(tags[t]) + '</div><div class="tag-editor-delete ' + className(tags[t]) + '"><i></i></div>');
                        }
                        else {
                            elements.push('<div class="tag-editor-tag normal">' + escape(tags[t]) + '</div>');
                        }
                    }
                    input.parent().parent().replaceWith('<li>' + elements.join('</li><li>') + '</li>');
                    update_globals();
                }
                else {
                    input.parent().html(escape(tags.join(''))).removeClass('active');
                }
                set_placeholder();
            });

            var pasted_content;
            ed.on('paste', 'input', function (e) {
                $(this).removeAttr('maxlength');
                pasted_content = $(this);
                setTimeout(function () {
                    tagCleanup(pasted_content);
                }, 30);
            });

            // keypress delimiter
            var inp;
            ed.on('keypress', 'input', function (e) {
                if (String.fromCharCode(e.which) === ' ') {
                    inp = $(this);
                    setTimeout(function () {
                        tagCleanup(inp);
                    }, 20);
                }
            });

            ed.on('keydown', 'input', function (e) {
                var $t = $(this),
                    next_tag,
                    prev_tag;

                // left/up key + backspace key on empty field
                if ((e.which === 37 || !o.autocomplete && e.which === 38) && !$t.caret() || e.which === 8 && !$t.val()) {
                    prev_tag = $t.closest('li').prev('li').find('.tag-editor-tag');
                    if (prev_tag.length) {
                        prev_tag.click().find('input').caret(-1);
                    }
                    else if ($t.val() && !(o.maxTags && ed.data('tags').length >= o.maxTags)) {
                        $(new_tag).insertBefore($t.closest('li')).find('.tag-editor-tag').click();
                    }
                    return false;
                }
                // right/down key
                else if ((e.which === 39 || !o.autocomplete && e.which === 40) && ($t.caret() === $t.val().length)) {
                    next_tag = $t.closest('li').next('li').find('.tag-editor-tag');
                    if (next_tag.length) {
                        next_tag.click().find('input').caret(0);
                    }
                    else if ($t.val()) {
                        ed.click();
                    }
                    return false;
                }
                // del key
                else if (e.which === 46 && (!$.trim($t.val()) || ($t.caret() === $t.val().length))) {
                    next_tag = $t.closest('li').next('li').find('.tag-editor-tag');
                    if (next_tag.length) {
                        next_tag.click().find('input').caret(0);
                    }
                    else if ($t.val()) {
                        ed.click();
                    }
                    return false;
                }
                // enter key
                else if (e.which === 13) {
                    ed.trigger('click', [$t.closest('li').next('li').find('.tag-editor-tag')]);

                    // trigger blur if maxTags limit is reached
                    if (o.maxTags && ed.data('tags').length >= o.maxTags) {
                        ed.find('input').blur();
                    }
                    return false;
                }
                // pos1
                else if (e.which === 36 && !$t.caret()) {
                    ed.find('.tag-editor-tag').first().click();
                }// end
                else if (e.which === 35 && $t.caret() === $t.val().length) {
                    ed.find('.tag-editor-tag').last().click();
                }// esc
                else if (e.which === 27) {
                    $t.val($t.data('old_tag') ? $t.data('old_tag') : '').blur();
                    return false;
                }
            });

            // create initial tags
            var tags = o.initialTags.length ? o.initialTags : splitTags(el.val());
            for (var i = 0; i < tags.length; i++) {
                if (o.maxTags && i >= o.maxTags) {
                    break;
                }
                var tag = tags[i];
                if (tag) {
                    tag_list.push(tag);
                    if (isMustache(tag)) {
                        ed.append('<li><div class="tag-editor-tag ' + className(tag) + '">' + escape(tag) + '</div><div class="tag-editor-delete ' + className(tag) + '"><i></i></div></li>');
                    }
                    else {
                        ed.append('<li><div class="tag-editor-tag normal">' + escape(tag) + '</div></li>');
                    }
                }
            }
            update_globals(true); // true -> no onChange callback

            // init sortable
            if (o.sortable && $.fn.sortable) {
                ed.sortable({
                    distance: 5,
                    cancel: 'input',
                    helper: 'clone',
                    update: function () { update_globals(); }
                });
            }
        });
    };

    $.fn.tagEditor.defaults = {
        initialTags: [],
        allowedTags: [],
        maxTags: 0,
        maxLength: 256,
        placeholder: '',
        clickDelete: false,
        animateDelete: 100,
        sortable: true, // jQuery UI sortable
        autocomplete: null, // options dict for jQuery UI autocomplete

        // callbacks
        onChange: function (field, editor, tags) {},
        beforeTagSave: function () {},
        beforeTagDelete: function () {}
    };
}(jQuery));
