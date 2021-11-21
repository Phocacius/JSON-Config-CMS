<?php

/**
 * Just displays a text in the edit form. Can be used to display more context information.
 * Does not save anything to the database.
 * Use either text or html depending on what you want to display
 *
 * Optional configuration:
 * - text (string): info text to be displayed as plain text
 * - html (string): info text to be displayed as html
 */
class Info extends DataType {

    public function __construct($config) {
        parent::__construct($config);
        $this->saveToDb = false;
    }

    function renderBackendForm(): string {

        if(array_key_exists("html", $this->config)) {
            return "<p>".$this->config['html']."</p>\n";
        }

        if(array_key_exists("text", $this->config)) {
            return "<p>".str_replace("\n", "<br>", htmlspecialchars($this->config['text']))."</p>\n";
        }

        return "";
    }
}
