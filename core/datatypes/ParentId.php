<?php

/**
 * Use this field if your table allows a hierarchy. The database column should be a nullable int.
 * The backend table will render children of an entry indented below the parent.
 * In the edit form you can select the parent in a select form element
 *
 * No additional configuration
 */
class ParentId extends DataType {

    private $titleField;

    function renderBackendForm(): string {
        if(!($this->parentRoute instanceof BackendTableRoute)) return "";
        $json = $this->parentRoute->loadData($this->parentRoute->tableName);
        $sortingFieldIndex = array_search("sortorder", array_column($json['fields'], 'type'));
        $this->titleField = array_key_exists("titleFieldName", $json) ? $json['titleFieldName'] : "title";
        $canBeSorted = $sortingFieldIndex !== false;

        if ($canBeSorted) {
            $sortingFieldName = $json['fields'][$sortingFieldIndex]['name'];
            $entries = DB::queryArray("SELECT id, " . $this->name . ", $this->titleField FROM " . $this->parentRoute->tableName . " ORDER BY $sortingFieldName");
        } else {
            $entries = DB::queryArray("SELECT id, " . $this->name . ", $this->titleField FROM " . $this->parentRoute->tableName);
        }

        $entries = $this->parentRoute->resolveChildrenRecursive($entries, $this->name);

        $output = "<select class=\"form-control\" id=\"input-$this->name\" name=\"$this->name\">
            <option value=\"null\"" . ($this->value == null ? " selected" : "") . ">Oberste Ebene</option>";

        $output .= $this->printHierarchy($entries);

        $output .= "</select>";
        return $output;
    }

    private function printHierarchy(array $entries, $level = 0) {
        $output = "";
        foreach ($entries as $entry) {
            if ($entry['id'] == $this->parentRoute->id) continue;
            $output .= "<option value=\"" . $entry['id'] . "\"";
            if ($this->value == $entry['id']) $output .= " selected";
            $output .= ">";
            $output .= str_repeat("--", $level);
            $output .= $entry[$this->titleField] . "</option>";
            $output .= $this->printHierarchy($entry['_children'], $level + 1);
        }
        return $output;
    }

    public function renderBackendTable($value): string {
        return "<div class=\"drag-handle\"></div>";
    }

    public function processValue($value) {
        return $value === 'null' ? null : (int)$value;
    }
}
