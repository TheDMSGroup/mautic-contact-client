<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$style          = $contactclient['style'];
$props          = $contactclient['properties'];
$useScrollEvent = in_array($props['when'], ['scroll_slight', 'scroll_middle', 'scroll_bottom']);
$useUnloadEvent = ($props['when'] == 'leave');
$useTimeout     = (int) $props['timeout'];
if ($props['when'] == '5seconds') {
    $useTimeout = 5;
} elseif ($props['when'] == 'minute') {
    $useTimeout = 60;
}
if ($useTimeout) {
    $timeout = $useTimeout * 1000;
}

$debug          = ($app->getEnvironment() == 'dev') ? 'true' : 'false';
$animate        = (!isset($props['animate']) || !empty($props['animate']));
$linkActivation = (!isset($props['link_activation']) || !empty($props['link_activation']));

if (!isset($preview)) {
    $preview = false;
}

if (!isset($clickUrl)) {
    $clickUrl = $props['content']['link_url'];
}

$cssContent = $view->render(
    'MauticContactClientBundle:Builder:style.less.php',
    [
        'preview' => $preview,
        'contactclient'   => $contactclient,
    ]
);
$cssContent = $view->escape($cssContent, 'js');

$parentCssContent = $view->render(
    'MauticContactClientBundle:Builder:parent.less.php',
    [
        'preview' => $preview,
    ]
);
$parentCssContent = $view->escape($parentCssContent, 'js');

switch ($style) {
    case 'bar':
        $iframeClass = "mf-bar-iframe mf-bar-iframe-{$props['bar']['placement']} mf-bar-iframe-{$props['bar']['size']}";
        if ($props['bar']['sticky']) {
            $iframeClass .= ' mf-bar-iframe-sticky';
        }
        break;

    case 'modal':
    case 'notification':
        $placement   = str_replace('_', '-', $props[$style]['placement']);
        $iframeClass = "mf-{$style}-iframe mf-{$style}-iframe-{$placement}";
        break;

    default:
        $iframeClass = 'mf-'.$style.'-iframe';
        break;
}
?>
(function (window) {
    if (typeof window.MauticContactClientParentHeadStyleInserted == 'undefined') {
        window.MauticContactClientParentHeadStyleInserted = false;
    }

    window.MauticContactClient<?php echo $contactclient['id']; ?> = function () {
        var ContactClient = {
            debug: <?php echo $debug; ?>,
            modalsDismissed: {},
            ignoreConverted: <?php echo ($contactclient['type'] !== 'notification' && !empty($props['stop_after_conversion'])) ? 'true' : 'false'; ?>,

            // Initialize the contactclient
            initialize: function () {
                if (ContactClient.debug)
                    console.log('initialize()');

                ContactClient.insertStyleIntoHead();
                ContactClient.registerContactClientEvent();

                // Add class to body
                ContactClient.addClass(document.getElementsByTagName('body')[0], 'MauticContactClient<?php echo ucfirst($style); ?>');
            },

            // Register click events for toggling bar, closing windows, etc
            registerClickEvents: function () {
                <?php if ($style == 'bar'): ?>
                var collapser = document.getElementsByClassName('mf-bar-collapser-<?php echo $contactclient['id']; ?>');

                collapser[0].addEventListener('click', function () {
                    ContactClient.toggleBarCollapse(collapser[0], false);
                });

                <?php else: ?>
                var closer = ContactClient.iframeDoc.getElementsByClassName('mf-<?php echo $style; ?>-close');
                var aTag = closer[0].getElementsByTagName('a');
                var container = ContactClient.iframeDoc.getElementsByClassName('mf-<?php echo $style; ?>');

                container.onclick = function(e) {
                    if (e) { e.stopPropagation(); }
                    else { window.event.cancelBubble = true; }
                };
                document.onclick = function() {
                    aTag[0].click();
                };

                aTag[0].addEventListener('click', function (event) {
                    // Prevent multiple engagements for link clicks on exit intent
                    ContactClient.modalsDismissed["<?php echo $contactclient['id']; ?>"] = true;

                    // Remove iframe
                    if (ContactClient.iframe.parentNode) {
                        ContactClient.iframe.parentNode.removeChild(ContactClient.iframe);
                    }

                    var overlays = document.getElementsByClassName('mf-modal-overlay-<?php echo $contactclient['id']; ?>');
                    if (overlays.length) {
                        overlays[0].parentNode.removeChild(overlays[0]);
                    }
                });
                <?php endif; ?>

                <?php if ($contactclient['type'] == 'click'): ?>
                var links = ContactClient.iframeDoc.getElementsByClassName('mf-link');
                if (links.length) {
                    links[0].addEventListener('click', function (event) {
                        ContactClient.convertVisitor();
                    });
                }
                <?php elseif ($contactclient['type'] == 'form'): ?>
                var buttons = ContactClient.iframeDoc.getElementsByClassName('mauticform-button');
                if (buttons.length) {
                    buttons[0].addEventListener('click', function (event) {
                        ContactClient.convertVisitor();
                    });
                }
                <?php endif; ?>
            },

            toggleBarCollapse: function (collapser, useCookie) {
                var svg = collapser.getElementsByTagName('svg');
                var g = svg[0].getElementsByTagName('g');
                var currentSize = svg[0].getAttribute('data-transform-size');
                var currentDirection = svg[0].getAttribute('data-transform-direction');
                var currentScale = svg[0].getAttribute('data-transform-scale');

                if (useCookie) {
                    if (ContactClient.cookies.hasItem('mf-bar-collapser-<?php echo $contactclient['id']; ?>')) {
                        var newDirection = ContactClient.cookies.getItem('mf-bar-collapser-<?php echo $contactclient['id']; ?>');
                        if (isNaN(newDirection)) {
                            var newDirection = currentDirection;
                        }
                    } else {
                        // Set cookie with current direction
                        var newDirection = currentDirection;
                    }
                } else {
                    var newDirection = (parseInt(currentDirection) * -1);
                    ContactClient.cookies.setItem('mf-bar-collapser-<?php echo $contactclient['id']; ?>', newDirection);
                }

                setTimeout(function () {
                    g[0].setAttribute('transform', 'scale(' + currentScale + ') rotate(' + newDirection + ' ' + currentSize + ' ' + currentSize + ')');
                    svg[0].setAttribute('data-transform-direction', newDirection);
                }, 500);

                var isTop = ContactClient.hasClass(ContactClient.iframeContactClient, 'mf-bar-top');
                if ((!isTop && newDirection == 90) || (isTop && newDirection == -90)) {
                    // Open it up
                    if (isTop) {
                        ContactClient.iframe.style.marginTop = 0;
                    } else {
                        ContactClient.iframe.style.marginBottom = 0;
                    }

                    ContactClient.removeClass(collapser, 'mf-bar-collapsed');
                    ContactClient.enableIframeResizer();

                } else {
                    // Collapse it
                    var iframeHeight = ContactClient.iframe.style.height;

                    iframeHeight.replace('px', '');
                    var newMargin = (parseInt(iframeHeight) * -1) + 'px';
                    if (isTop) {
                        ContactClient.iframe.style.marginTop = newMargin;
                    } else {
                        ContactClient.iframe.style.marginBottom = newMargin;
                    }

                    ContactClient.addClass(collapser, 'mf-bar-collapsed');
                    ContactClient.disableIFrameResizer();
                }
            },

            // Register scroll events, etc
            registerContactClientEvent: function () {
                if (ContactClient.debug)
                    console.log('registerContactClientEvent()');

                <?php if ($useScrollEvent): ?>
                if (ContactClient.debug)
                    console.log('scroll event registered');

                <?php if ($useTimeout): ?>
                if (ContactClient.debug)
                    console.log('timeout event registered');

                setTimeout(function () {
                    window.addEventListener('scroll', ContactClient.engageVisitorAtScrollPosition);
                }, <?php echo $timeout; ?>);

                <?php else: ?>

                window.addEventListener('scroll', ContactClient.engageVisitorAtScrollPosition);

                <?php endif; ?>

                <?php elseif ($useUnloadEvent): ?>
                if (ContactClient.debug)
                    console.log('show when visitor leaves');

                <?php if ($useTimeout): ?>
                if (ContactClient.debug)
                    console.log('timeout event registered');

                setTimeout(function () {
                    document.documentElement.addEventListener('mouseleave', ContactClient.engageVisitor);
                }, <?php echo $timeout; ?>);

                <?php else: ?>

                document.documentElement.addEventListener('mouseleave', ContactClient.engageVisitor);

                <?php endif; ?>

                // Add a listener to every link
                <?php if ($linkActivation): ?>

                var elements = document.getElementsByTagName('a');

                for (var i = 0, len = elements.length; i < len; i++) {
                    var href = elements[i].getAttribute('href');
                    if (href && href.indexOf('#') != 0 && href.indexOf('javascript:') != 0) {
                        elements[i].onclick = function (event) {
                            if (typeof ContactClient.modalsDismissed["<?php echo $contactclient['id']; ?>"] == 'undefined') {
                                if (ContactClient.engageVisitor()) {
                                    event.preventDefault();
                                }
                            }
                        }
                    }
                }
                <?php endif; ?>

                <?php else: ?>
                if (ContactClient.debug)
                    console.log('show immediately');

                <?php if ($useTimeout): ?>
                if (ContactClient.debug)
                    console.log('timeout event registered');

                setTimeout(function () {
                    // Give a slight delay to allow browser to process style injection into header
                    ContactClient.engageVisitor();
                }, <?php echo $timeout; ?>);

                <?php else: ?>

                // Give a slight delay to allow browser to process style injection into header
                ContactClient.engageVisitor();

                <?php endif; ?>

                <?php endif; ?>
            },

            // Insert global style into page head
            insertStyleIntoHead: function () {
                if (!window.MauticContactClientParentHeadStyleInserted) {
                    if (ContactClient.debug)
                        console.log('insertStyleIntoHead()');

                    var css = "<?php echo $parentCssContent; ?>",
                        head = document.head || document.getElementsByTagName('head')[0],
                        style = document.createElement('style');

                    head.appendChild(style);
                    style.type = 'text/css';
                    if (style.styleSheet) {
                        style.styleSheet.cssText = css;
                    } else {
                        style.appendChild(document.createTextNode(css));
                    }
                } else if (ContactClient.debug) {
                    console.log('Shared style already inserted into head');
                }
            },

            // Inserts styling into the iframe's head
            insertContactClientStyleIntoIframeHead: function () {
                // Insert style into iframe header
                var frameDoc = ContactClient.iframe.contentDocument;
                var frameHead = frameDoc.getElementsByTagName('head').item(0);

                var css = "<?php echo $cssContent; ?>";
                var style = frameDoc.createElement('style');

                style.type = 'text/css';
                if (style.styleSheet) {
                    style.styleSheet.cssText = css;
                } else {
                    style.appendChild(frameDoc.createTextNode(css));
                }
                frameHead.appendChild(style);

                var metaTag = frameDoc.createElement('meta');
                metaTag.name = "viewport"
                metaTag.content = "width=device-width,initial-scale=1,minimum-scale=1.0 maximum-scale=1.0"
                frameHead.appendChild(metaTag);
            },

            // Generates the contactclient HTML
            engageVisitor: function () {
                var now = Math.floor(Date.now() / 1000);

                if (ContactClient.cookies.hasItem('mautic_contactclient_<?php echo $contactclient['id']; ?>')) {
                    if (ContactClient.debug)
                        console.log('Cookie exists thus checking frequency');

                    var lastEngaged = parseInt(ContactClient.cookies.getItem('mautic_contactclient_<?php echo $contactclient['id']; ?>')),
                        frequency = '<?php echo $props['frequency']; ?>',
                        engage;

                    if (ContactClient.ignoreConverted && lastEngaged == -1) {
                        if (ContactClient.debug)
                            console.log('Visitor converted; abort');

                        return false;
                    }

                    switch (frequency) {
                        case 'once':
                            engage = false;
                            if (ContactClient.debug)
                                console.log('Engage once, abort');

                            break;
                        case 'everypage':
                            engage = true;
                            if (ContactClient.debug)
                                console.log('Engage on every page, continue');

                            break;
                        case 'q2min':
                            engage = (now - lastEngaged) >= 120;
                            if (ContactClient.debug) {
                                var debugMsg = 'Engage q2 minute, ';
                                if (engage) {
                                    debugMsg += 'continue';
                                } else {
                                    debugMsg += 'engage in ' + (120 - (now - lastEngaged)) + ' seconds';
                                }
                                console.log(debugMsg);
                            }

                            break;
                        case 'q15min':
                            engage = (now - lastEngaged) >= 900;
                            if (ContactClient.debug) {
                                var debugMsg = 'Engage q15 minute, ';
                                if (engage) {
                                    debugMsg += 'continue';
                                } else {
                                    debugMsg += 'engage in ' + (120 - (now - lastEngaged)) + ' seconds';
                                }
                                console.log(debugMsg);
                            }

                            break;
                        case 'hourly':
                            engage = (now - lastEngaged) >= 3600;
                            if (ContactClient.debug) {
                                var debugMsg = 'Engage hourly, ';
                                if (engage) {
                                    debugMsg += 'continue';
                                } else {
                                    debugMsg += 'engage in ' + (120 - (now - lastEngaged)) + ' seconds';
                                }
                                console.log(debugMsg);
                            }

                            break;
                        case 'daily':
                            engage = (now - lastEngaged) >= 86400;
                            if (ContactClient.debug) {
                                var debugMsg = 'Engage daily, ';
                                if (engage) {
                                    debugMsg += 'continue';
                                } else {
                                    debugMsg += 'engage in ' + (120 - (now - lastEngaged)) + ' seconds';
                                }
                                console.log(debugMsg);
                            }

                            break;
                    }

                    if (!engage) {

                        return false;
                    }
                }

                if (ContactClient.debug)
                    console.log('engageVisitor()');

                // Inject iframe
                ContactClient.createIframe();

                // Inject content into iframe
                ContactClient.iframeDoc.open();
                ContactClient.iframeDoc.write("{contactclient_content}");
                ContactClient.iframeDoc.close();

                var animate = <?php echo ($animate) ? 'true' : 'false'; ?>;

                ContactClient.iframe.onload = function() {
                    if (ContactClient.debug)
                        console.log('iframe loaded for '+ContactClient.iframe.getAttribute('src'));

                    // Resize iframe
                    if (ContactClient.enableIframeResizer()) {
                        // Give iframe chance to resize
                        setTimeout(function () {
                            if (animate) {
                                ContactClient.addClass(ContactClient.iframe, "mf-animate");
                            }
                            ContactClient.addClass(ContactClient.iframe, "mf-loaded");
                        }, 35);
                    } else {
                        if (animate) {
                            ContactClient.addClass(ContactClient.iframe, "mf-animate");
                        }
                        ContactClient.addClass(ContactClient.iframe, "mf-loaded");
                    }
                }

                // Set body margin to 0
                ContactClient.iframeDoc.getElementsByTagName('body')[0].style.margin = 0;

                // Find elements that should be moved to parent
                var move = ContactClient.iframeDoc.getElementsByClassName('mf-move-to-parent');
                for (var i = 0; i < move.length; i++) {
                    var bodyFirstChild = document.body.firstChild;
                    ContactClient.addClass(move[i], 'mf-moved-<?php echo $contactclient['id']; ?>');
                    bodyFirstChild.parentNode.insertBefore(move[i], ContactClient.iframe);
                }

                // Find elements that should be copied to parent
                var copy = ContactClient.iframeDoc.getElementsByClassName('mf-copy-to-parent');
                for (var i = 0; i < copy.length; i++) {
                    var bodyFirstChild = document.body.firstChild;
                    var clone = copy[i].cloneNode(true);
                    ContactClient.addClass(clone, 'mf-moved-<?php echo $contactclient['id']; ?>');
                    bodyFirstChild.parentNode.insertBefore(clone, ContactClient.iframe);
                }

                // Get the main contactclient element
                var contactclient = ContactClient.iframeDoc.getElementsByClassName('mautic-contactclient');
                ContactClient.iframeContactClient = contactclient[0];

                // Insert style into iframe head
                ContactClient.insertContactClientStyleIntoIframeHead();

                // Register events
                ContactClient.registerClickEvents();

                <?php if ($props['when'] == 'leave'): ?>
                // Ensure user can leave
                document.documentElement.removeEventListener('mouseleave', ContactClient.engageVisitor);
                <?php endif; ?>

                // Add cookie of last engagement
                if (ContactClient.debug)
                    console.log('mautic_contactclient_<?php echo $contactclient['id']; ?> cookie set for ' + now);

                ContactClient.cookies.removeItem('mautic_contactclient_<?php echo $contactclient['id']; ?>');
                ContactClient.cookies.setItem('mautic_contactclient_<?php echo $contactclient['id']; ?>', now, Infinity, '/');

                <?php if ($style == 'bar'): ?>
                var collapser = document.getElementsByClassName('mf-bar-collapser-<?php echo $contactclient['id']; ?>');

                if (animate) {
                    // Give iframe chance to resize
                    setTimeout(function () {
                        ContactClient.toggleBarCollapse(collapser[0], true);
                    }, 35);
                } else {
                    ContactClient.toggleBarCollapse(collapser[0], true);
                }
                <?php endif; ?>

                return true;
            },

            // Enable iframe resizer
            enableIframeResizer: function () {
                if (typeof ContactClient.iframeResizerEnabled !== 'undefined') {
                    return true;
                }

                <?php if (in_array($style, ['modal', 'notification', 'bar'])): ?>
                ContactClient.iframeHeight = 0;
                ContactClient.iframeWidth = 0;
                ContactClient.iframeResizeInterval = setInterval(function () {
                    if (ContactClient.iframeHeight !== ContactClient.iframe.style.height) {
                        var useHeight = ((window.innerHeight < ContactClient.iframeContactClient.offsetHeight) ?
                            window.innerHeight : ContactClient.iframeContactClient.offsetHeight);

                        useHeight += 10;
                        useHeight = useHeight + 'px';

                        if (ContactClient.debug) {
                            console.log('window inner height = ' + window.innerHeight);
                            console.log('iframe offset height = ' + ContactClient.iframeContactClient.offsetHeight);
                            console.log('iframe height set to ' + useHeight)
                        }

                        ContactClient.iframe.style.height = useHeight;
                        ContactClient.iframeHeight = useHeight;
                    }

                    <?php if (in_array($style, ['modal', 'notification'])): ?>
                    if (ContactClient.iframeWidth !== ContactClient.iframe.style.width) {
                        if (ContactClient.debug) {
                            console.log('window inner width = ' + window.innerWidth);
                            console.log('iframe offset width = ' +  ContactClient.iframeContactClient.offsetWidth);
                        }

                        if (window.innerWidth <  ContactClient.iframeContactClient.offsetWidth) {
                            // Responsive iframe
                            ContactClient.addClass(ContactClient.iframeContactClient, 'mf-responsive');
                            ContactClient.addClass(ContactClient.iframe, 'mf-responsive');
                            ContactClient.iframe.style.width = window.innerWidth + 'px';
                            ContactClient.iframe.width = window.innerWidth;
                            if (ContactClient.debug)
                                console.log('iframe set to responsive width: ');

                        } else {
                            ContactClient.iframe.style.width =  ContactClient.iframeContactClient.offsetWidth + 'px';
                            ContactClient.iframe.width =  ContactClient.iframeContactClient.offsetWidth + 'px';
                            ContactClient.removeClass(ContactClient.iframeContactClient, 'mf-responsive');
                            ContactClient.removeClass(ContactClient.iframe, 'mf-responsive');

                            if (ContactClient.debug)
                                console.log('iframe not a responsive width');
                        }

                        ContactClient.iframeWidth = ContactClient.iframe.style.width;
                    }
                    <?php endif; ?>
                }, 35);

                ContactClient.iframeResizerEnabled = true;

                return true;
                <?php endif; ?>

                return false;
            },

            // Disable iframe resizer
            disableIFrameResizer: function () {
                if (typeof ContactClient.iframeResizerEnabled !== 'undefined') {
                    delete(ContactClient.iframeResizerEnabled);
                }

                <?php if (in_array($style, ['modal', 'notification', 'bar'])): ?>
                clearInterval(ContactClient.iframeResizeInterval);
                <?php endif; ?>
            },

            // Create iframe to load into body
            createIframe: function () {
                if (ContactClient.debug)
                    console.log('createIframe()');

                ContactClient.iframe = document.createElement('iframe');
                ContactClient.iframe.style.border = 0;
                ContactClient.iframe.style.width = "100%";
                ContactClient.iframe.style.height = "100%";
                ContactClient.iframe.src = "about:blank";
                ContactClient.iframe.scrolling = "no";
                ContactClient.iframe.className = "<?php echo $iframeClass; ?>";

                var bodyFirstChild = document.body.firstChild;
                bodyFirstChild.parentNode.insertBefore(ContactClient.iframe, bodyFirstChild);

                ContactClient.iframeDoc = ContactClient.iframe.contentWindow.document;
            },

            // Execute event at current position
            engageVisitorAtScrollPosition: function (event) {
                var visualHeight = "innerHeight" in window
                    ? window.innerHeight
                    : document.documentElement.offsetHeight;

                var scrollPos = window.pageYOffset,
                    atPos = 0;

                <?php switch ($props['when']):
                case 'scroll_slight': ?>
                atPos = 10;
                <?php break; ?>

                <?php case 'scroll_middle': ?>
                scrollPos += (visualHeight / 2);
                atPos = (document.body.scrollHeight / 2);
                <?php break; ?>

                <?php case 'scroll_bottom': ?>
                scrollPos += visualHeight;
                atPos = document.body.scrollHeight;
                <?php break; ?>

                <?php endswitch; ?>

                if (ContactClient.debug)
                    console.log('scrolling: ' + scrollPos + ' >= ' + atPos);

                if (scrollPos >= atPos) {
                    window.removeEventListener('scroll', ContactClient.engageVisitorAtScrollPosition);
                    ContactClient.engageVisitor();
                }
            },

            // Create cookie noting visitor has been converted if applicable
            convertVisitor: function () {
                if (ContactClient.ignoreConverted) {
                    if (ContactClient.debug)
                        console.log('Visitor converted');

                    ContactClient.cookies.setItem('mautic_contactclient_<?php echo $contactclient['id']; ?>', -1, Infinity);
                } else if (ContactClient.debug) {
                    console.log('Visitor converted but ignoreConverted not enabled');
                }
            },

            // Element has class
            hasClass: function (element, hasClass) {
                return ( (" " + element.className + " ").replace(/[\n\t]/g, " ").indexOf(" " + hasClass + " ") > -1 );
            },

            // Add class to element
            addClass: function (element, addClass) {
                if (!ContactClient.hasClass(element, addClass)) {
                    element.className += " " + addClass;
                }
            },

            // Remove class from element
            removeClass: function (element, removeClass) {
                element.className = element.className.replace(new RegExp('\\b' + removeClass + '\\b'), '');
            },

            // Cookie handling
            cookies: {
                /**
                 * :: cookies.js ::
                 * https://developer.mozilla.org/en-US/docs/Web/API/document.cookie
                 * http://www.gnu.org/licenses/gpl-3.0-standalone.html
                 */
                getItem: function (sKey) {
                    if (!sKey) {
                        return null;
                    }
                    return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
                },
                setItem: function (sKey, sValue, vEnd, sPath, sDomain, bSecure) {
                    if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) {
                        return false;
                    }

                    this.removeItem(sKey);

                    var sExpires = "";
                    if (vEnd) {
                        switch (vEnd.constructor) {
                            case Number:
                                sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + vEnd;
                                break;
                            case String:
                                sExpires = "; expires=" + vEnd;
                                break;
                            case Date:
                                sExpires = "; expires=" + vEnd.toUTCString();
                                break;
                        }
                    }
                    document.cookie = encodeURIComponent(sKey) + "=" + encodeURIComponent(sValue) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
                    return true;
                },
                removeItem: function (sKey, sPath, sDomain) {
                    if (!this.hasItem(sKey)) {
                        return false;
                    }
                    document.cookie = encodeURIComponent(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "");
                    return true;
                },
                hasItem: function (sKey) {
                    if (!sKey) {
                        return false;
                    }
                    return (new RegExp("(?:^|;\\s*)" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=")).test(document.cookie);
                },
                keys: function () {
                    var aKeys = document.cookie.replace(/((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "").split(/\s*(?:\=[^;]*)?;\s*/);
                    for (var nLen = aKeys.length, nIdx = 0; nIdx < nLen; nIdx++) {
                        aKeys[nIdx] = decodeURIComponent(aKeys[nIdx]);
                    }
                    return aKeys;
                }
            }
        };

        return ContactClient;
    }

    // Initialize
    MauticContactClient<?php echo $contactclient['id']; ?>().initialize();
})(window);