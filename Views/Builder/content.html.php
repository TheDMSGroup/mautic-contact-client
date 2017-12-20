<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$templateBase = 'MauticContactClientBundle:Builder\\'.ucfirst($contactclient['style']).':index.html.php';
if (!isset($preview)) {
    $preview = false;
}

if (!isset($clickUrl)) {
    $clickUrl = '#';
}

$props = $contactclient['properties'];
?>

<div>
    <style scoped>
        .mautic-contactclient {
            font-family: <?php echo $props['content']['font']; ?>;
            color: #<?php echo $props['colors']['text']; ?>;
        }

        <?php if (isset($props['colors'])): ?>

        .mf-content a.mf-link, .mf-content .mauticform-button {
            background-color: #<?php echo $props['colors']['button']; ?>;
            color: #<?php echo $props['colors']['button_text']; ?>;
        }

        .mauticform-input:contactclient, select:contactclient {
            border: 1px solid #<?php echo $props['colors']['button']; ?>;
        }

        <?php endif; ?>
        <?php
        if (!empty($preview)):
            echo $view->render('MauticContactClientBundle:Builder:style.less.php',
                [
                    'preview' => true,
                    'contactclient'   => $contactclient,
                ]
            );
        endif;
        ?>
    </style>
    <?php echo $view->render(
        $templateBase,
        [
            'contactclient'    => $contactclient,
            'preview'  => $preview,
            'clickUrl' => $clickUrl,
            'htmlMode' => $htmlMode,
        ]
    );

    // Add view tracking image
    if (!$preview): ?>

        <img src="<?php echo $view['router']->url(
            'mautic_contactclient_pixel',
            ['id' => $contactclient['id']],
            true
        ); ?>" alt="Mautic ContactClient" style="display: none;"/>
    <?php endif; ?>
</div>