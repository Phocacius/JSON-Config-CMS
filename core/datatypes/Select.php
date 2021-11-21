<?php

/**
 * Display a select/dropdown field where the exactly one option can be chosen
 *
 * Required configuration fields:
 * - `options` (array<string> or map<string, string>) all available options. In the backend the key
 *   will be saved, in case of a json array this will be the index. Beware of this when modifying options
 *   later
 *
 * @see BitMap for multi-selection
 */
class Select extends DataType {

    function renderBackendForm(): string {
        $options = $this->config['options'];
        if(!is_array($options)) {
            return "<div class='alert alert-danger'>Bitte geben Sie die Auswahloptionen in der Konfigurations-JSON an.</div>\n<br>";
        }

        $output = "<select class=\"form-control\" id=\"input-$this->name\" name=\"$this->name\">";
        foreach ($options as $_key => $value) {
            $key = is_numeric($_key) ? $value : $_key;
            $selected = $this->value == $key ? " selected" : "";
            $output .= "<option value=\"$key\" $selected>$value</option>";
        }
        return $output."</select>\n";
    }

    public function renderBackendTable($value): string {
        if(array_key_exists($value, $this->config['options'])) {
            return $this->config['options'][$value];
        }
        return parent::renderBackendTable($value);
    }
}
