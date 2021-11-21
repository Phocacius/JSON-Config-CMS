<?php

/**
 * Display multiple related checkboxes
 * The result will be saved into a single integer column in the database using flag notation
 * like in chmod: The first option has the bit value 1, the second option 2, the third option 4 etc.
 *
 * Required configuration fields:
 * - `options` (array<string>)
 * @link CheckBox
 */
class BitMap extends DataType {

    function renderBackendForm(): string {
        $html = "";
        for($i = 0; $i < count($this->config['options']); $i++) {
            $number = pow(2, $i);
            $html .= "<div><label><input  type=\"checkbox\" id=\"" . $this->name . "[$i]\" name=\"" . $this->name . "[$i]\" value=\"1\"";
            if (($this->value & $number) > 0) $html .= " checked";
            $html .= " /> ".$this->config['options'][$i]."</label></div>";

        }
        return $html . "<br>\n";
    }

    public function renderBackendTable($value): string {
        $value = (int) $value;
        $checked = [];
        for($i = 0; $i < count($this->config['options']); $i++) {
            if(($value & pow(2, $i)) > 0) array_push($checked, $this->config['options'][$i]);
        }
        return count($checked) == 0 ? "–" : implode(", ", $checked);
    }

    public function processValue($value) {
        $result = 0;
        for($i = 0; $i < count($this->config['options']); $i++) {
            if(is_array($value) && array_key_exists($i, $value)) {
                $result += pow(2, $i);
            }
        }
        return $result;
    }

    public function processValueNotContainedInPost() {
        return 0;
    }
}
