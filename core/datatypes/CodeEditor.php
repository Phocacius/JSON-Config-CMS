<?php

/**
 * Displays a code editor using CodeMirror (https://codemirror.net/index.html)
 * Requires the library to be downloaded into `js/lib/codemirror` and `css/codemirror.css`
 *
 * Optional configuration fields:
 * - `rows` (int): number of lines initially displayed, default 5
 * - `language` (string): mime type of the language that should be entered. Syntax highlighting depends on this setting. Default `application/json`
 * - `default` (string): default code that will be used when no value has yet been saved
 */
class CodeEditor extends DataType {

    public function registerScripts(ScriptLoader $scriptLoader) {
        $scriptLoader->addExternalScript(BASEURL."/js/lib/codemirror/codemirror.js");
        $scriptLoader->addExternalScript(BASEURL."/js/lib/codemirror/mode/javascript/javascript.js");
    }

    function renderBackendForm(): string {
        $rows = array_key_exists('rows', $this->config) ? $this->config['rows'] : 5;
        $language = array_key_exists('language', $this->config) ? $this->config['language'] : 'application/json';
        $required = $this->required ? "required" : "";
        $value = $this->value ?: (array_key_exists("default", $this->config) ? $this->config['default'] : null);

        return "<textarea class=\"form-control\" rows=\"$rows\" id=\"input-$this->name\" name=\"$this->name\" $required>$value</textarea>\n
            <link rel='stylesheet' href='".BASEURL."/css/codemirror.css"."' type='text/css' />
            <script type='text/javascript'>
                var editor = CodeMirror.fromTextArea(document.getElementById('input-".$this->name."'), {
                    lineNumbers: true,
                    mode: '".$language."'
                  });
            </script>\n";
    }
}
