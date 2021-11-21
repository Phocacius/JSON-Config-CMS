<?php

/**
 * Displays a WYSIWYG using TinyMCE (https://www.tiny.cloud/get-tiny/downloads/)
 * Requires the library to be downloaded into `js/tinymce`
 *
 * Optional configuration fields:
 * - `rows` (int): number of lines initially displayed, default 5
 * - `preview` (map): options for the backend table preview with the following fields:
 *     - plainText (boolean): if true, the backend table preview will be without html tags
 *     - maxCharacters (int): maximum number of characters displayed in the preview
 */
class WYSIWYG extends DataType {

    public function registerScripts(ScriptLoader $scriptLoader) {
        $scriptLoader->addExternalScript(BASEURL."/js/tinymce/tinymce.min.js");
        $scriptLoader->addExternalScript(BASEURL."/js/tinymce/jquery.tinymce.min.js");
    }

    public function renderBackendTable($value): string {
        if(array_key_exists("preview", $this->config)) {
            $config = $this->config['preview'];
            if(array_key_exists("plainText", $config) && $config['plainText']) {
                $value = strip_tags($value);
            }
            if(array_key_exists("maxCharacters", $config)) {
                $splittedValue = explode("\n", wordwrap($value, $config['maxCharacters']))[0];
                $value = strlen($splittedValue) != strlen($value) ? $splittedValue." …" : $value;
            }
        }
        return $value || "";
    }

    function renderBackendForm(): string {
        $rows = array_key_exists('rows', $this->config) ? $this->config['rows'] : 5;
        $required = $this->required ? "required" : "";
        return "<textarea class=\"form-control\" rows=\"$rows\" id=\"input-$this->name\" name=\"$this->name\" $required>$this->value</textarea>\n
            <script type='text/javascript'>tinymce.init({
            	selector: '#input-$this->name',
            	language: 'de',
            	plugins : 'advlist autolink link image lists charmap print preview contextmenu hr code insertdatetime textcolor'
            });</script>\n";
    }
}
