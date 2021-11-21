<?php

/**
 * Displays a single checkbox. The result will be saved as 1 (set) or 0 (unset)  in the database
 * When `showInTable` is set to true, the value can be toggled right from the backend table
 *
 * No additional configuration
 *
 * @link BitMap can display multiple checkboxes in one field
 */
class CheckBox extends DataType {

    function renderBackendForm(): string {
        $html = "<div style=\"float: right; \"><input  type=\"checkbox\" id=\"" . $this->name . "\" name=\"" . $this->name . "\" value=\"1\"";
        if ($this->value) $html .= " checked";
        return $html . " /></div><br>\n";
    }

    public function renderBackendTable($value): string {
        $html = "<input class=\"backend-checkbox form-control\" data-field=\"" . $this->name . "\" type=\"checkbox\"";
        if ($value) $html .= " checked";
        return $html . "/>\n";
    }

    public function processValue($value) {
        // if checkbox values are contained in the post data, it means checked, otherwise it will be reset to 0.
        return 1;
    }

    public function processValueNotContainedInPost() {
        return 0;
    }
}
