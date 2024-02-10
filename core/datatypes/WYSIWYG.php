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
 *     - toolbar (string): The string containing all available toolbar options, see
 *         https://www.tiny.cloud/docs-4x/configure/editor-appearance/#toolbar
 *     - buttons (array of object): Additional toolbar buttons. Must contain the attributes icon (string), label (string) and text (string).
 *         The text will be inserted at the current mouse position when clicking on the button
 */
class WYSIWYG extends DataType {

    public function registerScripts(ScriptLoader $scriptLoader) {
        $scriptLoader->addExternalScript(BASEURL . "/js/tinymce/tinymce.min.js");
        $scriptLoader->addExternalScript(BASEURL . "/js/tinymce/jquery.tinymce.min.js");
    }

    public function renderBackendTable($value): string {
        if (array_key_exists("preview", $this->config)) {
            $config = $this->config['preview'];
            if (array_key_exists("plainText", $config) && $config['plainText']) {
                $value = strip_tags($value);
            }
            if (array_key_exists("maxCharacters", $config)) {
                $splittedValue = explode("\n", wordwrap($value, $config['maxCharacters']))[0];
                $value = strlen($splittedValue) != strlen($value) ? ($splittedValue . " …") : $value;
            }
        }
        return $value ?? "";
    }

    function renderBackendForm(): string {
        $rows = array_key_exists('rows', $this->config) ? $this->config['rows'] : 5;
        $required = $this->required ? "required" : "";
        $setup = "";
        $toolbar = array_key_exists('toolbar', $this->config) ? $this->config["toolbar"] :
            "undo redo | styleselect | fontsizeselect | bold italic | alignleft aligncenter alignright alignjustify | outdent indent";

        if (array_key_exists("buttons", $this->config)) {
            $setup = ', setup: function(editor) {';
            $toolbar .= " | ";
            $i = 0;
            foreach($this->config["buttons"] as $button) {
                $setup .= "editor.addButton('custom".$i."', {icon: '".$button["icon"]."', tooltip: '".$button['label']."', onclick: function () {
        editor.insertContent('".$button["text"]."');}});";
                $toolbar .= "custom".$i." ";
                $i++;
            }
            $setup .= "}";
        }
        return "<textarea class=\"form-control\" rows=\"$rows\" id=\"input-$this->name\" name=\"$this->name\" $required>$this->value</textarea>\n
            <script type='text/javascript'>tinymce.init({
            	selector: '#input-$this->name',
            	language: 'de',
            	toolbar: '".$toolbar."',
            	fontsize_formats: '0.5rem 0.7rem 0.8rem 0.9rem 1rem 1.1rem 1.2rem 1.3rem 1.5rem 2rem',
            	content_style: 'body { font-size: 1rem; }',
            	plugins : 'advlist autolink link image lists charmap print preview contextmenu hr code insertdatetime textcolor'" . $setup . "
            });</script>\n";
    }
}
