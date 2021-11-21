<?php
/**
 * Defines a field that references on another table, can be used e.g. for referencing a post to a user
 * 
 * Mandatory Configuration:
 * linkedTable: tableName of the referenced table
 *
 * Optional Configuration:
 * primaryKey: column name of the primary key of the referenced table. Defaults to "id"
 * displayTemplate: Template to format the referenced row in the backend. Column names can be referenced by enclosing them in curly brackets. E.g. "{first_name} {last_name}". Defaults to displaying the primaryKey.
 * editDisplayTemplate: like display template, but used for the select field while editing. Defaults to displayTemplate
 * where: SQL where clause, can be used restrict the available options. E.g. "'enabled' = 1"
 * selectFields: to optimise performance, select the columns to be queried. Make sure all columns reference in the displayTemplate are included here. Defaults to "*"
 * allowEmptyValue: allows the field to be null. Defaults to true
 */
class Relation extends DataType {

    function renderBackendForm(): string {
        if(!array_key_exists("linkedTable", $this->config)) {
            return "<div class='alert alert-danger'>Bitte geben Sie die verlinkte Tabelle (<code>linkedTable</code>) in der Konfigurations-JSON an.</div>\n";
        }

        $table = $this->config['linkedTable'];
        $primaryKey = array_key_exists("primaryKey", $this->config) ? $this->config['primaryKey'] : "id";
        $whereClause = array_key_exists("where", $this->config) ? " WHERE ".$this->config['where'] : "";
        $selectFields = array_key_exists("selectFields", $this->config) ? $this->config['selectFields'] : "*";
        $_displayTemplate = array_key_exists("displayTemplate", $this->config) ? $this->config['displayTemplate'] : "{".$primaryKey."}";
        $displayTemplate = array_key_exists("editDisplayTemplate", $this->config) ? $this->config['editDisplayTemplate'] : $_displayTemplate;
        $allowEmptyValue = $this->readBooleanConfigValue("allowEmptyValue", true);

        $fullTable = DB::queryArray("SELECT $selectFields FROM `$table`$whereClause");
        $options = [];
        foreach ($fullTable as $row) {
            $options[$row[$primaryKey]] = preg_replace_callback("/{([a-zA-Z0-9_-]*)}/", function($matches) use ($row) {
                return $row[$matches[1]];
            }, $displayTemplate);
        }

        if(count($options) == 0) {
            return "<div class='alert alert-warning'>Keine verlinkten Einträge gefunden.</div>\n";
        }

        $output = "<select class=\"form-control\" id=\"input-$this->name\" name=\"$this->name\">";
        if($allowEmptyValue) $output .= "<option value=\"null\">–</option>";
        foreach ($options as $key => $value) {
            $selected = $this->value == $key ? " selected" : "";
            $output .= "<option value=\"$key\" $selected>$value</option>";
        }
        return $output."</select>\n";
    }

    public function renderBackendTable($value): string {
        if(!$value) return "–";

        $table = $this->config['linkedTable'];
        $primaryKey = array_key_exists("primaryKey", $this->config) ? $this->config['primaryKey'] : "id";
        $selectFields = array_key_exists("selectFields", $this->config) ? $this->config['selectFields'] : "*";
        $displayTemplate = array_key_exists("displayTemplate", $this->config) ? $this->config['displayTemplate'] : "{".$primaryKey."}";
        if(!is_numeric($value)) {
            $value = "'$value'";
        }

        $result = DB::queryArray("SELECT $selectFields FROM `$table` WHERE `$primaryKey` = $value");
        if(count($result) == 0) return "–";

        $row = $result[0];
        return preg_replace_callback("/{([a-zA-Z0-9_-]*)}/", function($matches) use ($row) {
            return $row[$matches[1]];
        }, $displayTemplate);
    }

    public function processValue($value) {
        if($value === 'null') return null;
        return $value;
    }
}
