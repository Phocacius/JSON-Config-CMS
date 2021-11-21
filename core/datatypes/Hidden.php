<?php

/**
 * A field that is not editable in the backend form.
 * It can be used for fields that are modified from outside this backend or AUTO_INCREMENT fields.
 * It can be rendered in the backend table, something like this will display the id:
 *
 * {
    "name": "id",
    "labelShort": "#",
    "type": "hidden"
    }
 */
class Hidden extends DataType {

    function renderBackendForm(): string {
        return "<input type=\"hidden\" id=\"input-$this->name\" name=\"$this->name\" value=\"".htmlentities($this->value)."\">\n";
    }
}
