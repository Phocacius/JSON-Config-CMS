<?php

/**
 * Abstract class representing a data type like a text or image.
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
 * @see SpecialDataType
 */
abstract class DataType {

    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * Called when editing an entry of this DataType or creating a new entry
     * Use `$this->name` as the input field's name (for the POST data).
     * `$this->value` will contain the current value
     * @return string a html containing an input field or similar for rendering in the backend
     */
    abstract function renderBackendForm(): string;

    /**
     * override this method if your backend edit field requires additional javascript libraries
     * this might be relevant e.g. for a WYSIWYG editor or a map
     * @param ScriptLoader $scriptLoader
     */
    public function registerScripts(ScriptLoader $scriptLoader) { }

    /**
     * Called after a form has been submitted and is about to be saved
     * Override this if you need to process the value that should be saved into the database
     * For a file input for example, you can move the uploaded file here or crop images to the required size
     * @param string $value the value as extracted from the $_POST or $_FILE data
     * @return mixed the value that will be saved into the database
     */
    public function processValue($value) {
        return $value;
    }

    /**
     * This method is called after a form has been submitted for fields, where the POST data
     * does not contain the name of this field. This is useful e.g. for checkboxes, where
     * unchecked fields are not contained in the POST data
     * @return mixed the value that will be saved into the database
     * @link processValue
     */
    public function processValueNotContainedInPost() {
        return null;
    }

    /**
     * Called when this field is rendered in the backend table.
     * Will only be called when `showInTable` is set to true in the json configuration
     * @param mixed $value the value of this field as read from the database
     * @return string html output that will be displayed in the backend table
     */
    public function renderBackendTable($value): string {
        return $value === null ? "" : $value;
    }

    /**
     * called when this field should be viewed. This may be useful e.g. for files
     * do not return a string from this method but use echo or `$this->parentRoute->renderTemplate`
     * @param mixed $value
     */
    public function viewRaw($value) {
        echo $this->renderBackendTable($value);
    }

    /**
     * this field's json configuration
     * @var array
     */
    protected $config = [];

    /**
     *
     * @var bool
     */
    public $required = false;

    /**
     * The route this field is part of
     * @var Route
     */
    public $parentRoute;

    /**
     * @var string
     */
    public $name = "";

    /**
     * @var string
     */
    public $value = "";

    /**
     * Determines whether this field will be saved to the database. Make sure a table column with the same name as
     * this field's name exists. Having it set to false make sense e.g. for just informational fields
     * @var bool
     */
    public $saveToDb = true;

    /**
     * Utility function to read a boolean value from this field's json configuration
     * @param string $key the key of the configuration attribute to be read
     * @param bool $default default value to be returned when the field is not set in the configuration
     * @return bool the boolean value as contained in the configuration or the $default value
     */
    protected function readBooleanConfigValue(string $key, bool $default = false): bool {
        return array_key_exists($key, $this->config) ? !(!$this->config[$key] || $this->config[$key] === 'false') : $default;
    }

}