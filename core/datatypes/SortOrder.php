<?php

/**
 * Use this field if your table allows sorting. The database column should be integer.
 * The backend table will render drag handles. Make sure to set `showInTable` to true.
 * When also using `ParentId`, note that sorting only occurs by parent, not globally
 *
 * Note that pagination will be disabled when a sortOrder field is present
 *
 * No additional configuration
 */
class SortOrder extends DataType {

    function renderBackendForm(): string {
        return "<p>Aktuelle Position: " . $this->value . "<br>Die Sortierung kann über Drag&Drop auf der Übersichtsseite geändert werden.</p>\n
                <input type=\"hidden\" id=\"input-$this->name\" name=\"$this->name\" value=\"" . htmlentities($this->value) . "\">\n";
    }

    public function renderBackendTable($value): string {
        return "<div class=\"drag-handle-wrapper\"><div class=\"drag-handle\"></div></div>";
    }

    public function processValue($value) {
        return $value;
    }
}
