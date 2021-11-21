<?php

/**
 * Factory for instantiating a given DataType for a given json configuration field
 * If you use a custom data type on your website, add your class extending from [DataType]
 * in classes/datatypes, name the file like your class and in your json, use the exact same
 * class name (including case).
 * e.g.
 *  {
 *      "name": "custom",
 *      "label": "Custom Field",
 *      "type": "MyCustomField",
 *      "showInTable": true,
 *      "config": {
 *          ...
 *      }
 *  }
 */
class DataTypeFactory {
    public static function create($model): DataType {
        $config = array_key_exists("config", $model) ? $model['config'] : array();

        switch ($model['type']) {
            case "hidden": return new Hidden($config);
            case "input": return new Input($config);
            case "info": return new Info($config);
            case "select": return new Select($config);
            case "textarea": return new Textarea($config);
            case "special": return new Special($config);
            case "image": return new Image($config);
            case "wysiwyg": return new WYSIWYG($config);
            case "code": return new CodeEditor($config);
            case "readonly": return new ReadOnly($config);
            case "location": return new GeoLocation($config);
            case "checkbox": return new CheckBox($config);
            case "bitmap": return new BitMap($config);
            case "sortorder": return new SortOrder($config);
            case "parent": return new ParentId($config);
            case "relation": return new Relation($config);
            case "file": return new File($config);
            default:
                if (class_exists($model['type'])) {
                    $type = $model['type']; return new $type($config);
                }
                throw new RuntimeException("unknown data type '" . $model['type'] . "' requested.");
        }
    }
}
