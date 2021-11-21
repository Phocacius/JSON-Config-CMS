<?php

/**
 * A field that is not editable in the backend form.
 * It can be used for fields that are modified by other actions, e.g. a version information that will be increased automatically on save
 * (for this, use @link BackendTableRoute::processDataBeforeSaving())
 *
 * Optional configuration:
 * - default (mixed): Initial value of this field when no value has been saved yet
 */
class ReadOnly extends DataType {

    function renderBackendForm(): string {
        $value = $this->value ?: (array_key_exists("default", $this->config) ? $this->config['default'] : null);
        return "<p>".$value . "</p>\n
                <input type=\"hidden\" id=\"input-$this->name\" name=\"$this->name\" value=\"" . htmlentities($value) . "\">\n";
    }

}
