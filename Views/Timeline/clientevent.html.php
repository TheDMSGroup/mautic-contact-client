<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$message = $event['extra']['message'];
$logs    = $event['extra']['logs'];
?>

<dl class="dl-horizontal small">
    <dt><?php echo $view['translator']->trans('mautic.contactclient.timeline.logs.message'); ?></dt>
    <dd><?php echo $message; ?></dd>
    <div class="small" style="max-width: 100%;">
        <strong><?php echo $view['translator']->trans('mautic.contactclient.timeline.logs.heading'); ?></strong>
        <br/>
        <textarea class="codeMirror-json"><?php echo json_encode($logs, JSON_PRETTY_PRINT); ?></textarea>
    </div>
</dl>

<script defer>
    var codeMirror = function ($el) {
        if (!$el.hasClass('codemirror-active')) {
            var $textarea = $el.find('textarea.codeMirror-json');
            if ($textarea.length) {
                CodeMirror.fromTextArea($textarea[0], {
                    mode: {
                        name: 'javascript',
                        json: true
                    },
                    theme: 'cc',
                    gutters: [],
                    lineNumbers: false,
                    lineWrapping: true,
                    readOnly: true
                });
            }
            $el.addClass('codemirror-active');
        }
    };
    mQuery('#contact-timeline a[data-activate-details=\'all\']').on('click', function () {
        if (mQuery(this).find('span').first().hasClass('fa-level-down')) {
            mQuery('#contact-timeline a[data-activate-details!=\'all\']').each(function () {
                var detailsId = mQuery(this).data('activate-details'),
                    $details = mQuery('#timeline-details-' + detailsId);
                if (detailsId && $details.length) {
                    $details.removeClass('hide');
                    codeMirror($details);
                    mQuery(this).addClass('active');
                }
            });
        }
        else {
            mQuery('#contact-timeline a[data-activate-details!=\'all\']').each(function () {
                var detailsId = mQuery(this).data('activate-details'),
                    $details = mQuery('#timeline-details-' + detailsId);
                if (detailsId && $details.length) {
                    $details.addClass('hide');
                    mQuery(this).removeClass('active');
                }
            });
        }
    });

mQuery(document).ready(function(){ 
    $buttons = mQuery('.contact-client-button').parent().parent();
    $buttons.on('click', function(){ 
        mQuery('textarea.codeMirror-json').each(function(i, element){ 
            if(mQuery(element).is(':visible')) {
            CodeMirror.fromTextArea(element, {
                mode: {
                    name: 'javascript',
                    json: true
                },
                theme: 'cc',
                gutters: [],
                lineNumbers: false,
                lineWrapping: true,
                readOnly: true
            });
            }
    }); 
    }); 
});
</script>
<?php
echo $view['assets']->includeStylesheet('plugins/MauticContactClientBundle/Assets/build/contactclient.min.css');
?>
