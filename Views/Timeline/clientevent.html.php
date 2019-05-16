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
    <dt>Message:</dt>
    <dd><?=$message; ?></dd>
    <div class="small" style="max-width: 100%;">
        <strong><?php echo $view['translator']->trans('mautic.contactsource.timeline.logs.heading'); ?></strong>
        <br/>
        <textarea class="codeMirror-json"><?php echo $logs; ?></textarea>
    </div>
</dl>

<script defer>
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
