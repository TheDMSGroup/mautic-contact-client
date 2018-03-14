/* Overlay CodeMirror editor modes with custom mustache mode to style our mustache tags */
var CodeMirrorMustacheOverlay = function (config, parserConfig) {
    console.log(config);
    var originalMode = config.mode.replace('/mustache', '');
    if (originalMode === 'json') {
        originalMode = {
            name: 'javascript',
            json: true
        };
    }
    return CodeMirror.overlayMode(CodeMirror.getMode(config, originalMode || parserConfig.backdrop || 'text/html'), {
        token: function (stream, state) {
            var ch,
                word = '';
            if (stream.match('{{')) {
                if (typeof window.CodeMirrorMustacheOverlayTokens === 'undefined') {
                    window.CodeMirrorMustacheOverlayTokens = [];
                }
                while ((ch = stream.next()) != null) {
                    if (ch !== '}') {
                        word += ch;
                    }
                    if (ch === '}' && stream.next() === '}') {
                        stream.eat('}');
                        if (!window.CodeMirrorMustacheOverlayTokens.length && typeof window.JSONEditor.tokenCache['plugin:mauticContactClient:getTokens'] !== 'undefined') {
                            mQuery.each(window.JSONEditor.tokenCache['plugin:mauticContactClient:getTokens'], function (key, value) {
                                window.CodeMirrorMustacheOverlayTokens.push(key);
                            });
                        }
                        if (window.CodeMirrorMustacheOverlayTokens.length && window.CodeMirrorMustacheOverlayTokens.indexOf(word.replace('#', '').replace('/', '')) === -1) {
                            return 'mustache-danger';
                        }
                        else if (word[0] === '#' || word[0] === '/') {
                            return 'mustache-warn';
                        }
                        else {
                            return 'mustache';
                        }
                    }
                }
            }
            while (stream.next() != null && !stream.match('{{', false)) {}
            return null;
        }
    });
};
CodeMirror.defineMode('text/html/mustache', CodeMirrorMustacheOverlay);
CodeMirror.defineMode('xml/mustache', CodeMirrorMustacheOverlay);
CodeMirror.defineMode('html/mustache', CodeMirrorMustacheOverlay);
CodeMirror.defineMode('yaml/mustache', CodeMirrorMustacheOverlay);
CodeMirror.defineMode('json/mustache', CodeMirrorMustacheOverlay);
