<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$props = $contactclient['properties'];

echo $view->render(
    'MauticContactClientBundle:Builder\Modal:index.html.php',
    [
        'contactclient'    => $contactclient,
        'preview'  => $preview,
        'clickUrl' => $clickUrl,
        'htmlMode' => $htmlMode,
    ]
);
