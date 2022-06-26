<?php

/**
 * Displays an input field that allows to enter plain text, numbers, emails and so on.
 *
 * Optional configuration:
 * - type (string): The input type. See https://www.w3schools.com/tags/tag_input.asp for options. default: text
 * - default (string): Initial value of this field when no value has been saved yet
 * - validations (array<string>): Validations/Modifications that will be performed on the value before saving. Currently available modifications:
 *      - lowercase: will transform all letters to lowercase
 *      - urlsegment: will replace all characters that are not alphanumeric to underscores except for .-/
 *
 * @see Textarea for multiline text input
 * @see WYSIWYG for formatted text input
 */
class Input extends DataType {
    public function renderBackendTable($value): string {
        return $value ? $value : "–";
    }


    function renderBackendForm(): string {
        $type = $this->config['type'] ?: "text";
        $required = $this->required ? "required" : "";
        $value = $type === 'password' ? '' : htmlentities($this->value);
        if (!$value && array_key_exists("default", $this->config)) {
            $value = $this->config['default'];
        }
        return "<input class=\"form-control\" type=\"$type\" id=\"input-$this->name\" name=\"$this->name\" value=\"" . $value . "\" $required>\n";
    }

    public function processValue($value) {
        if (array_key_exists("validations", $this->config) && is_array($this->config['validations'])) {
            foreach ($this->config['validations'] as $validation) {
                switch ($validation) {
                    case "lowercase":
                        $value = strtolower($value);
                        break;
                    case "urlsegment":
                        $value = preg_replace('/[^A-Za-z0-9_.-\/]/', '_', $value);
                        break;
                }
            }
        }
        return $value;
    }
}
