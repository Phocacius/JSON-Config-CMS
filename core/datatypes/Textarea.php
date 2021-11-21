<?php

/**
 *  Displays an multiline text input field
 *
 * Optional configuration:
 * - `default` (string): Initial value of this field when no value has been saved yet
 * - `rows` (int): number of lines initially displayed, default 3
 *
 * @see Input for single line input
 * @see WYSIWYG for formatted text input
 */
class Textarea extends DataType {

    function renderBackendForm(): string {
        $rows = array_key_exists("rows", $this->config) ? $this->config['rows'] : 3;
        $required = $this->required ? "required" : "";
        $value = $this->value;
        if (!$value && array_key_exists("default", $this->config)) {
            $value = $this->config['default'];
        }

        return "<textarea class=\"form-control\" rows=\"$rows\" id=\"input-$this->name\" name=\"$this->name\" $required>$value</textarea>\n";
    }
}
